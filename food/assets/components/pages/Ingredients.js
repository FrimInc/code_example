import React, {Component} from 'react';
import IngredientSimpleForm from '../controls/ingredients/IngredientSimpleForm';
import AppContext from "../app-params";
import {Button, Col, Row} from 'react-bootstrap';
import Loader from "../utils/Loader";
import {GrEdit} from "react-icons/all";
import PubStatusShow from "../controls/PubStatusShow";
import FilterLineCheckbox from "../controls/filters/FilterLineCheckbox";
import PageArrows from "../controls/PageArrows";

class Ingredients extends Component {
    static contextType = AppContext;

    filters;

    constructor(props) {
        super(props);
        this.filterValues = {
            page: 1,
            pageCount: 50
        };
        this.state = {ingredients: [], loading: true};

        this.filters = [];
    }

    componentDidMount() {
        this.filters = [
            {
                code: 'access',
                name: 'Видимость',
                options: {
                    multiple: true
                },
                vals: Object.entries(this.context.appData.user.accessViewsMap)
                    .reduce((prev, curr) => {
                        prev.push({'id': curr[0], 'name': curr[1]});
                        return prev;
                    }, [])
            },
            {
                code: 'type',
                name: 'Тип',
                options: {
                    multiple: true
                },
                vals: this.context.appData.ingredientType.reduce((prev, curr) => {
                    prev.push({'id': curr.id, 'name': curr.name});
                    return prev;
                }, [])
            }
        ]
        this.getIngredients();
        this.context.setTitle('Все ингредиенты');
    }

    locState = {};

    handleFilterChange = (newValue, filterLine) => {
        this.filterValues[filterLine.code] = newValue;
        this.getIngredients();
    }

    goToPage = (page) => {
        this.filterValues.page = page;
        this.getIngredients();
    }

    getIngredients = () => {
        this.context.obAxios.post(`/app/ingredients`, this.filterValues).then(obResponse => {
            this.setState({ingredients: obResponse, loading: false}, () => {
                this.locState = {}
            });
        })
    }

    deleteIngredient = (id) => {
        if (this.locState[id]) {
            return;
        }
        this.locState[id] = true;

        this.context.obAxios.post(`/app/ingredients/delete`, {id: id}).then(obResponse => {
            if (obResponse.status) {
                this.getIngredients();
                this.context.displaySuccess(obResponse.message);
            } else {
                this.context.displayError(obResponse.message);
            }
        })
    }

    publishIngredient = (id) => {
        if (this.locState[id]) {
            return;
        }
        this.locState[id] = true;
        this.context.obAxios.post(`/app/ingredients/publish`, {id: id}).then(obResponse => {
            if (obResponse.status) {
                this.getIngredients();
                this.context.displaySuccess(obResponse.message);
            } else {
                this.context.displayError(obResponse.message);
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
        const filter = this.filterValues;

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
                        <PageArrows
                            onPageChange={this.goToPage}
                            page={filter.page}
                        />
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
                                                ingredient.canDelete &&
                                                <Button
                                                    variant={"dark text-danger"}
                                                    size={'sm'}
                                                    className={'d-none d-md-inline-block'}
                                                    onClick={(e) => this.deleteIngredient(ingredient.id, e)}
                                                >
                                                    <i className={'bi bi-trash'}/>
                                                </Button>
                                            }
                                            {
                                                ingredient.canPublish &&
                                                <Button
                                                    variant={"primary"}
                                                    size={'sm'}
                                                    className={'ml-2 d-none d-md-inline-block'}
                                                    onClick={(e) => this.publishIngredient(ingredient.id, e)}
                                                >Опубликовать</Button>
                                            }

                                        </Col>
                                    </Row>
                            ))
                        }
                        <PageArrows
                            onPageChange={this.goToPage}
                            page={filter.page}
                        />
                        <h3>Добавить новый</h3>
                        <IngredientSimpleForm onSave={() => this.getIngredients()}/>
                    </Col>
                </Row>
            </>
        )
    }
}

export default Ingredients;