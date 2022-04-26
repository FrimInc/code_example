import React, {Component} from 'react';
import AppContext from "../app-params";
import {Button, Col, Form, ListGroup, Navbar, Row} from 'react-bootstrap';
import Loader from "../utils/Loader";
import IngredientSelector from "../controls/ingredients/IngredientSelector";
import HtmlSelect from "../controls/HtmlSelect";
import AmountSelector from "../controls/AmountSelector";
import TagSelector from "../controls/TagSelector";

class RecipeEdit extends Component {
    static contextType = AppContext;
    validated;

    constructor(props) {
        super(props);
        this.state = {
            values: {},
            loading: true,
            validated: true,
        };
        this.form = React.createRef();
    }

    componentDidMount() {
        this.getRecipe();
    }

    setRecipe = (recipe) => {
        this.context.setTitle('Рецепт - ' + recipe.name);
        this.setState(
            {
                values: recipe,
                loading: false
            }, () => this.checkValidation()
        );
    }

    getRecipe = () => {
        this.context.obAxios.get(`/app/recipe/` + this.props.match.params.id).then(obResponse => {
            if (this.context.app.checkNoError(obResponse)) {
                this.setRecipe(obResponse);
            } else {
                this.props.history.push('/recipes')
            }
        });
    }

    returnToView = () => {
        this.props.history.push('/recipe/' + (this.state.values.id ? this.state.values.id : ''));
    }

    submitForm = (event) => {
        event.preventDefault();
        event.stopPropagation();
        if (this.checkValidation()) {
            this.context.obAxios.post('/app/recipes/add', this.state.values).then(obResponse => {
                if (obResponse.status) {
                    this.context.displaySuccess(obResponse.message);
                    if (this.state.values.id !== obResponse.item.id) {
                        this.props.history.push('/recipe/' + obResponse.item.id);
                    } else {
                        this.props.history.push('/recipes');
                    }
                } else {
                    this.context.displayError(obResponse.message);
                }
            })
        }
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

    handleIngredientChange = (e) => {
        this.state.values.ingredients[e.target.attributes.index.value].ingredient[e.target.name] = e.target.value;
        this.setState(
            {
                values: {
                    ...this.state.values, ingredients: this.state.values.ingredients
                }
            }, () => this.checkValidation());
    }

    handleIngredientAmountChange = (value, index) => {
        if (typeof value === 'object') {
            value = value.target.value;
        }
        this.state.values.ingredients[index]['amount'] = value;
        this.setState(
            {
                values: {
                    ...this.state.values, ingredients: this.state.values.ingredients
                }
            }, () => this.checkValidation()
        );
    }

    handleIngredientTasteChange = (value, index) => {
        if (typeof value === 'object') {
            value = value.target.checked;
        }
        this.state.values.ingredients[index]['taste'] = value;
        this.state.values.ingredients[index]['amount'] = this.state.values.ingredients[index].ingredient.units.step;
        this.setState(
            {
                values: {
                    ...this.state.values, ingredients: this.state.values.ingredients
                }
            }, () => this.checkValidation()
        );
    }

    handleIngredientAdd = (ingredient) => { //TOTO фокус на поле с количеством
        let tmpIngredients = this.state.values.ingredients;
        tmpIngredients.push({
            id: '',
            amount: 0,
            ingredient: ingredient,
            taste: false,
            recipe: this.state.values.id
        });
        this.setState(
            {
                values: {
                    ...this.state.values, ingredients: tmpIngredients
                }
            }, () => this.checkValidation()
        );
    }

    handleIngredientDelete = (ingredientIndex) => {
        let tmpIngredients = this.state.values.ingredients;
        tmpIngredients.splice(ingredientIndex, 1);
        this.setState(
            {
                values: {
                    ...this.state.values, ingredients: tmpIngredients
                }
            }, () => this.checkValidation()
        );
    }

    handleIngredientMove = (ingredientIndex, direction) => {
        this.state.values.ingredients = this.doMove(ingredientIndex, direction, this.state.values.ingredients);

        this.setState(
            {
                values: {
                    ...this.state.values, ingredients: this.state.values.ingredients
                }
            }, () => this.checkValidation()
        );
    }

    handleStageMove = (stageIndex, direction) => {
        this.state.values.stages = this.doMove(stageIndex, direction, this.state.values.stages);

        this.setState(
            {
                values: {
                    ...this.state.values, stages: this.state.values.stages
                }
            }, () => this.checkValidation()
        );
    }

    doMove = (intIndex, direction, target) => {
        const tmpS = target[intIndex + direction];
        target[intIndex + direction] = target[stageIndex];
        target[intIndex] = tmpS;
        return target;
    }

    handleTagAdd = (tag) => {
        let tmpTags = this.state.values.tags;
        tmpTags.push({
            id: '',
            tag: tag,
            recipe: this.state.values.id
        });
        this.setState(
            {
                values: {
                    ...this.state.values, tags: tmpTags
                }
            }, () => this.checkValidation()
        );
    }

    handleTagDelete = (tagIndex) => {
        let tmpTags = this.state.values.tags;

        tmpTags.splice(tagIndex, 1);
        this.setState(
            {
                values: {
                    ...this.state.values, tags: tmpTags
                }
            }, () => this.checkValidation()
        );
    }


    handleInputChange = (e, name) => {

        if (typeof e !== 'object') {
            this.setState(
                {
                    values: {...this.state.values, [name]: e}
                }, () => this.checkValidation()
            );
        } else {
            this.setState(
                {
                    values: {...this.state.values, [e.target.name]: e.target.value}
                }, () => this.checkValidation()
            );
        }

    }

    focusOnStage = (stageIndex = 0) => {
        document.querySelectorAll("textarea[name=stage]")[stageIndex].focus()
    }

    checkTab = (e, stageIndex) => {
        if (e.key === 'Tab') {
            e.preventDefault();
            if (stageIndex === this.getLastIndex()) {
                this.handleStageAdd(this.getLastIndex(), () => {
                    this.focusOnStage(this.getLastIndex());
                });
            } else {
                this.focusOnStage(stageIndex + 1);
            }
        }
    }

    handleStageChange = (e) => {
        let stages = this.state.values.stages;
        stages[e.target.attributes.index.value] = e.target.value;
        this.setState(
            {
                values: {
                    ...this.state.values, stages: stages
                }
            }, () => this.checkValidation()
        );
    }

    handleStageAdd = (index = -1, callbackOnAdd) => {
        let newStages = [], indexC = 0;
        if (index === -1) {
            newStages.push('');
        }
        this.state.values.stages.map(stage => {
                newStages.push(stage);
                if (indexC === index) {
                    newStages.push('');
                }
                indexC++;
            }
        );
        this.setState(
            {
                values: {
                    ...this.state.values, stages: newStages
                }
            }, () => {
                if (typeof callbackOnAdd === 'function') {
                    callbackOnAdd();
                }
                this.checkValidation()
            }
        );
    }

    handleStageDelete = (index) => {
        this.state.values.stages.splice(index, 1);

        this.setState(
            {
                values: {
                    ...this.state.values, stages: this.state.values.stages
                }
            }, () => this.checkValidation()
        );

    }

    getLastIndex = () => {
        return this.state.values.stages ? this.state.values.stages.length - 1 : 0
    }

    render = () => {
        const loading = this.state.loading;
        const values = this.state.values;
        const validated = this.state.validated;

        const ingredientsLast = values.ingredients ? values.ingredients.length - 1 : 0;
        const stagesLast = this.getLastIndex();

        values.difficult ??= 3;
        return (
            loading ? <Loader/> : (
                <Form
                    noValidate
                    validated={validated}
                    ref={form => this.form.current = form}
                >
                    <Row>
                        <Col xs={12} lg={12}>
                            <Button
                                variant='link'
                                className={'mx-3'}
                                onClick={this.returnToView}
                            >Вернуться</Button>
                        </Col>
                        <Col xs={12} lg={12}>
                            <Row>
                                <Col xs={12} lg={6}>
                                    <Form.Group controlId="recipeName">
                                        <Form.Label>Название рецепта <sup><b>*</b></sup></Form.Label>
                                        <Form.Control
                                            required
                                            type="text"
                                            name="name"
                                            placeholder=""
                                            value={values.name}
                                            onChange={this.handleInputChange}
                                        />
                                        <Form.Control.Feedback type="invalid">
                                            Обязательно укажите название
                                        </Form.Control.Feedback>
                                        <Form.Text className="text-muted">
                                            Краткое и понятное название рецепта, например <i>Куриные котлеты в сливочном
                                            соусе</i>
                                        </Form.Text>
                                    </Form.Group>
                                    <Form.Group controlId="recipeDifficult">
                                        <Form.Label>Сложность приготовления</Form.Label>
                                        <Row>
                                            <Col xs={12}>
                                                Просто
                                                <span className={'ml-2'}>
                                {[1, 2, 3, 4, 5].map(diffValue => (
                                        <Form.Check key={diffValue}
                                                    type="radio"
                                                    inline
                                                    name='difficult'
                                                    checked={diffValue.toString() === values.difficult.toString()}
                                                    value={diffValue}
                                                    onChange={this.handleInputChange}
                                                    aria-label={'radio 1' + diffValue}
                                                    className={'mx-0'}
                                        />
                                    )
                                )}
                                </span>
                                                Сложно
                                            </Col>
                                        </Row>
                                    </Form.Group>
                                    <Form.Group controlId="recipeType">
                                        <Form.Label>Тип рецепта</Form.Label>
                                        <HtmlSelect
                                            name='type'
                                            current_value={values.type}
                                            onChange={this.handleInputChange}/>
                                    </Form.Group>
                                    <Form.Group controlId="recipeDescription">
                                        <Form.Label>Краткое описание</Form.Label>
                                        <Form.Control
                                            required
                                            type="text"
                                            as='textarea'
                                            name="anounce"
                                            placeholder=""
                                            value={values.anounce}
                                            onChange={this.handleInputChange}
                                        />
                                        <Form.Text className="text-muted">
                                            Небольшое описание рецепта.
                                        </Form.Text>
                                    </Form.Group>
                                </Col>
                                <Col xs={12} lg={6} className={'padding-110'}>
                                    <Form.Group controlId="recipeTimeTotal">
                                        <Form.Label>Общее время готовки <sup><b>*</b></sup></Form.Label>
                                        <AmountSelector
                                            opened={true}
                                            type="number"
                                            name="totalTime"
                                            value={values.totalTime}
                                            min={'1'}
                                            step={1}
                                            onChange={(e) => this.handleInputChange(e, 'totalTime')}
                                            counts={[5, 30]}
                                        />
                                    </Form.Group>
                                    <Form.Group controlId="recipeTimeActive">
                                        <Form.Label>Активное время готовки <sup><b>*</b></sup></Form.Label>
                                        <AmountSelector
                                            opened={true}
                                            type="number"
                                            name="activeTime"
                                            value={values.activeTime}
                                            min={'1'}
                                            step={1}
                                            onChange={(e) => this.handleInputChange(e, 'activeTime')}
                                            counts={[5, 30]}
                                        />
                                    </Form.Group>
                                    <Form.Group controlId="recipeKkal">
                                        <Form.Label>Калорийность (на 100 гр.)</Form.Label>
                                        <AmountSelector
                                            opened={true}
                                            type="number"
                                            name="kkal"
                                            value={values.kkal}
                                            min='0'
                                            step={5}
                                            onChange={(e) => this.handleInputChange(e, 'kkal')}
                                            counts={[10, 100]}
                                        />
                                    </Form.Group>
                                    <Form.Group controlId="recipeServing">
                                        <Form.Label>Количество порций <sup><b>*</b></sup></Form.Label>
                                        <Form.Control
                                            type="number"
                                            name="serving"
                                            value={values.serving}
                                            min={'1'}
                                            onChange={this.handleInputChange}
                                        />
                                    </Form.Group>
                                    <Form.Group controlId="recipeDays">
                                        <Form.Label>Дней хранения <sup><b>*</b></sup></Form.Label>
                                        <Form.Control
                                            type="number"
                                            name="days"
                                            value={values.days}
                                            min={'0'}
                                            onChange={this.handleInputChange}
                                        />
                                    </Form.Group>
                                </Col>
                            </Row>
                            <Form.Group controlId="tags">
                                <h4>Ключевые слова</h4>
                                <ListGroup>
                                    {
                                        values.tags.map((tag, tagIndex) =>
                                            <ListGroup.Item key={tagIndex}>
                                                {tag.tag.name}
                                                <Button variant={'link'}
                                                        size={'sm'}
                                                        className={'mt-1'}
                                                        onClick={() => this.handleIngredientDelete(tagIndex)}>
                                                    <i className={'bi bi-trash text-danger'}/>
                                                </Button>
                                            </ListGroup.Item>
                                        )
                                    }
                                    <TagSelector onSave={this.handleTagAdd}/>
                                </ListGroup>
                            </Form.Group>
                            <Form.Group controlId="recipeIngredients">
                                <h4>Ингредиенты <sup><b>*</b></sup></h4>
                                {
                                    values.ingredients.map((ingredient, ingredientIndex) =>
                                        <Row key={ingredientIndex}>
                                            <Col className={'d-none d-lg-block'} xs={1}>
                                                <Button
                                                    disabled={ingredientIndex === ingredientsLast}
                                                    size={'sm'}
                                                    variant={'light'}
                                                    onClick={() => this.handleIngredientMove(ingredientIndex, 1)}
                                                >
                                                    <i className={'bi bi-arrow-bar-down'}/>
                                                </Button>
                                                <Button
                                                    disabled={ingredientIndex === 0}
                                                    size={'sm'}
                                                    variant={'light'}
                                                    onClick={() => this.handleIngredientMove(ingredientIndex, -1)}
                                                >
                                                    <i className={'bi bi-arrow-bar-up'}/>
                                                </Button>
                                            </Col>
                                            <Col xs={12} lg={5}>
                                                <Form.Control
                                                    required
                                                    type="text"
                                                    index={ingredientIndex}
                                                    name='name'
                                                    placeholder="Введите название ингредиента"
                                                    value={ingredient.ingredient.name}
                                                    onChange={this.handleIngredientChange}
                                                />
                                            </Col>
                                            <Col xs={10} lg={5} className={'padding-110'}>
                                                <AmountSelector
                                                    taste={true}
                                                    type="number"
                                                    name='amount'
                                                    placeholder="количество"
                                                    index={ingredientIndex}
                                                    min={ingredient.ingredient.units.step}
                                                    step={ingredient.ingredient.units.step}
                                                    value={ingredient.amount}
                                                    tasteValue={ingredient.taste}
                                                    onChangeTaste={(value) => this.handleIngredientTasteChange(value, ingredientIndex)}
                                                    onChange={(value) => this.handleIngredientAmountChange(value, ingredientIndex)}
                                                    append={ingredient.ingredient.units.short}
                                                    counts={[ingredient.ingredient.units.step * 10, ingredient.ingredient.units.step * 100]}
                                                />
                                            </Col>
                                            <Col xs={2} lg={1}>
                                                <Button variant={'link'}
                                                        size={'sm'}
                                                        className={'mt-1'}
                                                        onClick={() => this.handleIngredientDelete(ingredientIndex)}>
                                                    <i className={'bi bi-trash text-danger'}/>
                                                </Button>
                                            </Col>
                                        </Row>
                                    )
                                }
                            </Form.Group>
                            <IngredientSelector onSave={this.handleIngredientAdd}/>
                            <h4 className={'mt-3'}>Подробное описание рецепта <sup><b>*</b></sup></h4>
                            <Row>
                                <Col xs={1}></Col>
                                <Col xs={11}>
                                    <Button
                                        variant={'link'}
                                        onClick={(e) => this.handleStageAdd(-1, e)}
                                        className={'btn-sm'}
                                    >
                                        <i className={'bi bi-plus-square'}/>
                                    </Button>
                                </Col>
                                <Col xs={12} className={'stages-container'}>
                                    {
                                        values.stages.map((stage, stageIndex) =>
                                            <Row key={stageIndex} className={'stage-row'}>
                                                <Col xs={1}>
                                                    <Button
                                                        disabled={stageIndex === 0}
                                                        size={'sm'}
                                                        variant={'light'}
                                                        onClick={() => this.handleStageMove(stageIndex, -1)}
                                                    >
                                                        <i className={'bi bi-arrow-bar-up'}/>
                                                    </Button>
                                                    <Button
                                                        disabled={stageIndex === stagesLast}
                                                        size={'sm'}
                                                        variant={'light'}
                                                        onClick={() => this.handleStageMove(stageIndex, 1)}
                                                    >
                                                        <i className={'bi bi-arrow-bar-down'}/>
                                                    </Button>
                                                </Col>
                                                <Col xs={8} lg={10}>
                                                    <Form.Control
                                                        required
                                                        index={stageIndex}
                                                        type="text"
                                                        as='textarea'
                                                        name="stage"
                                                        placeholder=""
                                                        value={stage}
                                                        onKeyDown={
                                                            (e) => this.checkTab(e, stageIndex)
                                                        }
                                                        onChange={this.handleStageChange}
                                                    />
                                                    <Button
                                                        className={'btn-sm'}
                                                        variant={'link'}
                                                        onClick={(e) => this.handleStageAdd(stageIndex, e)}
                                                    >
                                                        <i className={'bi bi-plus-square'}/>
                                                    </Button>
                                                </Col>
                                                <Col xs={3} lg={1}>
                                                    <Button
                                                        size={'sm'}
                                                        variant={'link'}
                                                        onClick={(e) => this.handleStageDelete(stageIndex, e)}
                                                    >
                                                        <i className={'bi bi-trash text-danger'}/>
                                                    </Button>
                                                </Col>
                                            </Row>
                                        )
                                    }
                                </Col>
                            </Row>
                        </Col>
                        <Navbar className={'w-100 buttons-navbar'} bg={'dark'} variant={'dark'}>
                            <Button
                                variant='success'
                                className={'mx-3'}
                                onClick={this.submitForm}
                            >Сохранить</Button>
                            <Button
                                variant='secondary'
                                className={'mx-3'}
                                onClick={this.returnToView}
                            >Отменить</Button>
                        </Navbar>
                    </Row>
                </Form>
            )
        )
    }
}

export default RecipeEdit;