import React, {Component} from 'react';
import AppContext from "../app-params";
import {Badge, Button, Col, Form, Navbar, Row} from 'react-bootstrap';
import Loader from "../utils/Loader";
import AmountSelector from "../controls/AmountSelector";
import IngredientSelector from "../controls/ingredients/IngredientSelector";
import {CopyToClipboard} from 'react-copy-to-clipboard';
import {FaCopy} from "react-icons/all";

class ShopListView extends Component {
    static contextType = AppContext;
    validated;
    submitTimeout;

    constructor(props) {
        super(props);
        this.state = {
            shopList: {},
            values: {},
            loading: true,
            validated: true,
        };

        this.form = React.createRef();
    }

    componentDidMount() {
        this.getShopList();
    }

    submitWithTimeout = () => {
        clearTimeout(this.submitTimeout);
        this.submitTimeout = setTimeout(this.submitForm, 1000);
    }

    handleInputChange = (e, name) => {
        if (typeof e !== 'object') {
            this.setState({
                values: {...this.state.values, [name]: e}
            }, this.submitWithTimeout);
        } else {
            this.setState({
                values: {...this.state.values, [e.target.name]: e.target.value}
            }, this.submitWithTimeout);
        }
        this.checkValidation();
    }

    checkValidation = () => {
        let validity = this.form.current.checkValidity();

        if (validity === false) {
            this.setState({validated: true});
            this.context.displayError('Проверьте правильность заполнения полей');
        } else {
            this.setState({validated: false});
        }

        return validity;
    }

    handleIngredientAdd = (ingredient) => {
        this.state.values.list.ingredients.push({
            id: '',
            amount: ingredient.minimum,
            ingredient: ingredient
        });
        this.setState({
            values: {
                ...this.state.values, list: this.state.values.list
            }
        }, () => this.checkShopList(false));

    }

    handleIngredientDelete = (ingredientIndex) => {
        let tmpList = this.state.values.list;
        tmpList.ingredients.splice(ingredientIndex, 1);
        this.setState({
                values: {
                    ...this.state.values, list: tmpList
                }
            }, this.submitWithTimeout
        );

    }

    handleIngredientAmountChange = (value, index) => {
        if (typeof value === 'object') {
            value = value.target.value;
        }

        let tmpList = this.state.values.list;
        tmpList.ingredients[index]['amount'] = value;

        this.setState({
            values: {
                ...this.state.values, list: tmpList
            }
        }, this.submitWithTimeout);
    }

    handleIngredientChecked = (value, ingredientIndex) => {
        let list = this.state.values.list;
        if (typeof value === 'object') {
            value = value.target.checked;
        }
        list.ingredients[ingredientIndex].isChecked = value;
        this.setState(
            {
                values: {
                    ...this.state.values, list: list
                }
            }, this.submitWithTimeout
        );
    }

    getShopList = () => {
        this.context.obAxios.get(`/app/shopList/` + this.props.id).then(obResponse => {
            if (this.context.app.checkNoError(obResponse)) {
                this.setState({values: obResponse, loading: false})
                this.context.setTitle('Список покупок - ' + obResponse.name);
            } else {
                this.props.history.push('/shopLists');
            }
        })
    }

    submitForm = (event) => {
        if (typeof event != 'undefined') {
            event.preventDefault();
            event.stopPropagation();
        }
        if (this.checkValidation()) {
            this.context.obAxios.post('/app/shopLists/add', this.state.values).then(obResponse => {
                if (obResponse.status) {
                    if (this.state.values.id !== obResponse.item.id) {
                        this.props.history.push('/shopList/' + obResponse.item.id);
                    }
                    this.setState({values: obResponse.item, loading: false}, () => {
                        if (typeof this.props.handleChange === 'function') {
                            this.props.handleChange();
                        }
                    });
                    this.context.displaySuccess(obResponse.message);
                } else {
                    this.context.displayError(obResponse.message);
                }
            })
        }
    }

    copyShopList = () => {
        this.context.obAxios.post('/app/shopLists/copy', this.state.values).then(obResponse => {
            if (obResponse.status) {
                if (this.state.values.id !== obResponse.item.id) {
                    this.setState({values: obResponse.item, loading: false});
                    this.props.history.push('/shopList/' + obResponse.item.id);
                }
                this.context.displaySuccess(obResponse.message);
            } else {
                this.context.displayError(obResponse.message);
            }
        })
    }

    checkShopList = (timeout = true) => {
        this.context.obAxios.post('/app/shopLists/check', this.state.values).then(obResponse => {
            if (obResponse.status) {
                this.setState(
                    {values: obResponse.item, loading: false},
                    timeout ? this.submitWithTimeout : this.submitForm
                );
                this.context.displaySuccess(obResponse.message);
            } else {
                this.context.displayError(obResponse.message);
            }
        })
    }

    makeIngredientRow = (ingredient, ingredientIndex) => {
        return (
            <Col xs={12} key={ingredientIndex}>
                <Row>
                    <Col className={'d-none d-lg-block'} lg={2}>
                        <Button
                            variant={ingredient.isChecked ? 'warning' : 'success'}
                            className={'w-100'}
                            onClick={() => this.handleIngredientChecked(!ingredient.isChecked, ingredientIndex)}
                        >
                            {ingredient.isChecked ? 'Отмена' : 'Купить'}
                        </Button>
                    </Col>
                    <Col xs={8} lg={5}>
                        <CopyToClipboard
                            text={ingredient.ingredient.name}
                        >
                            <FaCopy className={'mr-2 cursor-copy d-none d-lg-inline'}/>
                        </CopyToClipboard>
                        {ingredient.ingredient.name}
                    </Col>
                    <Col xs={4} lg={4} className={'padding-110'}>
                        <AmountSelector
                            className={'d-none d-lg-block'}
                            opened={true}
                            required
                            type="number"
                            name='amount'
                            placeholder="количество"
                            index={ingredientIndex}
                            min={ingredient.ingredient.units.step}
                            step={ingredient.ingredient.units.step}
                            value={ingredient.amount}
                            onChange={(value) => this.handleIngredientAmountChange(value, ingredientIndex)}
                            append={ingredient.ingredient.units.short}
                            counts={[ingredient.ingredient.units.step * 10, ingredient.ingredient.units.step * 100]}
                        />
                        <div className={'d-lg-none'}>
                            <h4>{ingredient.amount} {ingredient.ingredient.units.short}</h4>
                        </div>
                    </Col>
                    <Col xs={2} lg={1}>
                        <Button variant={'text-danger'}
                                size={'sm'}
                                className={'mt-1'}
                                onClick={() => this.handleIngredientDelete(ingredientIndex)}>
                            <i className={'bi bi-trash'}>&nbsp;</i>
                        </Button>
                    </Col>
                    <Col xs={10} className={'d-block d-lg-none'}>
                        <Button
                            variant={ingredient.isChecked ? 'warning' : 'success'}
                            className={'w-100'}
                            onClick={() => this.handleIngredientChecked(!ingredient.isChecked, ingredientIndex)}
                        >
                            {ingredient.isChecked ? 'Отмена' : 'Купить'}
                        </Button>
                    </Col>
                </Row>
            </Col>
        );
    }

    render() {
        const loading = this.state.loading;
        const shopList = this.state.values;
        const validated = this.state.validated;

        const props = this.props;

        const renderValues =
            props.filterChecked !== undefined
                ? [props.filterChecked]
                : [false, true];

        return (
            loading ? <Loader/> : (
                <Form
                    noValidate
                    validated={validated}
                    ref={form => this.form.current = form}
                >
                    <Row>
                        <Col xs={12} lg={12}>
                            {
                                !props.hideControls &&
                                <Form.Group controlId="shopListName" className={'d-none d-lg-block'}>
                                    <Form.Label>Название списка покупок <sup><b>*</b></sup></Form.Label>
                                    <Form.Control
                                        required
                                        type="text"
                                        name="name"
                                        placeholder=""
                                        value={shopList.name}
                                        onChange={this.handleInputChange}
                                    />
                                    <Form.Control.Feedback type="invalid">
                                        Обязательно укажите название
                                    </Form.Control.Feedback>
                                </Form.Group>
                            }
                            <h3 className={props.hideControls ? '' : 'd-none d-lg-none'}>
                                {
                                    shopList.main &&
                                    <Badge
                                        size={'sm'}
                                        variant={'secondary'}
                                    >
                                        Основной
                                    </Badge>
                                }
                                {shopList.name}
                            </h3>
                            <IngredientSelector onSave={this.handleIngredientAdd}/>
                            {
                                renderValues.map(isChecked => shopList.groupedList.map((group, groupId) =>
                                    shopList.list.ingredients.filter(
                                        (ingredient) =>
                                            ingredient.isChecked === isChecked
                                            && ingredient.ingredient.type.id === group.group.id
                                    ).length > 0 &&
                                    <Row key={groupId}>
                                        <Col xs={12}><h4>{group.group.name}</h4></Col>
                                        { //SORRY
                                            shopList.list.ingredients.map((ingredient, ingredientIndex) =>
                                                ingredient.isChecked === isChecked && ingredient.ingredient.type.id === group.group.id &&
                                                this.makeIngredientRow(ingredient, ingredientIndex)
                                            )
                                        }
                                    </Row>
                                ))
                            }
                        </Col>
                        {
                            !props.hideControls &&
                            <Col xs={12} lg={12} className={'d-none d-lg-block'}>
                                <Navbar className={'w-100 buttons-navbar'} bg={'dark'} variant={'dark'}>
                                    <Button
                                        className={'ml-1'}
                                        variant={'secondary'}
                                        onClick={this.copyShopList}>
                                        Копировать
                                    </Button>
                                </Navbar>
                            </Col>
                        }
                    </Row>
                </Form>
            )
        )
    }
}

export default ShopListView;