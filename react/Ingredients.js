import React, {Component} from 'react';
import IngredientSimpleForm from '../controls/ingredients/IngredientSimpleForm';
import axios from 'axios';
import AppContext from "../app-params";
import {Button, Col, Row} from 'react-bootstrap';
import Loader from "../utils/Loader";
import {GrEdit} from "react-icons/all";
import PubStatusShow from "../controls/PubStatusShow";
import FilterLineCheckbox from "../controls/FilterLineCheckbox";

class Ingredients extends Component {
    static contextType = AppContext;

    filters;

    constructor(props) {
        super(props);
        this.filterValues = {};
        this.state = {ingredients: [], loading: true};

        this.filters = [
            {
                code: 'access',
                name: 'Видимость',
                options: {
                    multiple: true
                },
                vals: this.props.appProps.user.accessViewsMap
            },
            {
                code: 'type',
                name: 'Тип',
                options: {
                    multiple: true
                },
                vals: this.props.appProps.ingredientType.sort((a, b) => {
                    return a.sort > b.sort;
                }).reduce((prev, curr) => {
                    prev[curr.id] = curr.name;
                    return prev;
                }, {})
            }
        ]
    }

    componentDidMount() {
        this.getIngredients();
        this.context.setTitle('Все ингредиенты');
    }

    locState = {};

    handleFilterChange = (newValue, filterLine) => {
        this.filterValues[filterLine.code] = newValue;
        this.getIngredients(this.filterValues);
    }

    getIngredients = (filter = {}) => {
        axios.post(`/app/ingredients`, filter).then(obResponse => {
            this.setState({ingredients: obResponse.data, loading: false}, () => {
                this.locState = {}
            });
        })
    }

    deleteIngredient = (id) => {
        if (this.locState[id]) {
            return;
        }
        this.locState[id] = true;

        axios.post(`/app/ingredients/delete`, {id: id}).then(obResponse => {
            if (obResponse.data.status) {
                this.getIngredients();
                this.context.displaySuccess(obResponse.data.message);
            } else {
                this.context.displayError(obResponse.data.message);
            }
        })
    }

    publishIngredient = (id) => {
        if (this.locState[id]) {
            return;
        }
        this.locState[id] = true;
        axios.post(`/app/ingredients/publish`, {id: id}).then(obResponse => {
            if (obResponse.data.status) {
                this.getIngredients();
                this.context.displaySuccess(obResponse.data.message);
            } else {
                this.context.displayError(obResponse.data.message);
            }
        })
    }

    lastEditingIndex;

    ingredientToggleEdit = (ingredientKey) => {
        if (this.lastEditingIndex > -1) {
            this.state.ingredients[this.lastEditingIndex].editing = false;
        }
        this.lastEditingIndex = ingredientKey;
        this.state.ingredients[ingredientKey].editing = !this.state.ingredients[ingredientKey].editing;
        this.setState(this.state);
    }

    render() {
        const loading = this.state.loading;
        return (
            <>
                <Row>
                    <Col lg={2} xs={12}>
                        {
                            this.filters.map(filterLine =>
                                <FilterLineCheckbox
                                    key={filterLine.code}
                                    filterValues={filterLine}
                                    obFilterChange={(filter) => this.handleFilterChange(filter, filterLine)}
                                />
                            )
                        }

                    </Col>
                    <Col lg={10} xs={12}>
                        <Row className={'border border-primary'}>
                            <Col lg={5} xs={6}>
                                Название
                            </Col>
                            <Col lg={2} xs={6}>
                                Тип
                            </Col>
                            <Col lg={2} xs={6}>
                                Мера
                            </Col>
                            <Col lg={2} xs={6}>
                                Минимум закупка
                            </Col>
                        </Row>
                        {loading ? <Loader/> : (
                            this.state.ingredients.map((ingredient, ingredientIndex) =>
                                ingredient.editing
                                    ? <IngredientSimpleForm key={ingredientIndex} ingredient={ingredient}
                                                            onSave={() => this.getIngredients()}/>
                                    :
                                    <Row
                                        className={'border-bottom border-left border-right border-primary ingredient-list-row'}
                                        key={ingredientIndex}>
                                        <Col lg={5} xs={6}>
                                            {
                                                ingredient.canEdit &&
                                                <GrEdit
                                                    className={'d-none d-md-inline'}
                                                    onClick={(e) => this.ingredientToggleEdit(ingredientIndex, e)}
                                                />
                                            }
                                            {
                                                ingredient.isMine &&
                                                <PubStatusShow item={ingredient}/>
                                            }
                                            {ingredient.name}
                                        </Col>
                                        <Col lg={3} xs={6}>
                                            {ingredient.type.name}
                                        </Col>
                                        <Col lg={1} xs={6}>
                                            {ingredient.units.short}
                                        </Col>
                                        <Col lg={1} xs={6}>
                                            {ingredient.minimum}
                                        </Col>
                                        <Col lg={2} xs={12}>
                                            {
                                                ingredient.canPublish &&
                                                <Button
                                                    variant={"success"}
                                                    size={'sm'}
                                                    className={'d-none d-md-inline-block'}
                                                    onClick={(e) => this.publishIngredient(ingredient.id, e)}
                                                >Опубликовать</Button>
                                            }
                                            {
                                                ingredient.canDelete &&
                                                <Button
                                                    variant={"link"}
                                                    color={'danger'}
                                                    size={'sm'}
                                                    className={'d-none d-md-inline-block'}
                                                    onClick={(e) => this.deleteIngredient(ingredient.id, e)}
                                                >
                                                    <i className={'bi bi-trash'}>&nbsp;</i>
                                                </Button>
                                            }
                                        </Col>
                                    </Row>
                            ))
                        }
                        <h3>Добавить новый</h3>
                        <IngredientSimpleForm onSave={() => this.getIngredients()}/>
                    </Col>
                </Row>
            </>
        )
    }
}

export default Ingredients;