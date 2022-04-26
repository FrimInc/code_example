import React, {Component} from 'react';
import AppContext from "../app-params";
import {Button, Col, ListGroup, Row} from 'react-bootstrap';
import Loader from "../utils/Loader";
import DifficultView from "../controls/DifficultView";
import OneIngredient from "../controls/ingredients/OneIngredient";
import {Link} from "react-router-dom";
import TypeShow from "../controls/TypeShow";
import PubStatusShow from "../controls/PubStatusShow";
import TimeView from "../controls/TimeView";
import FilterLineCheckbox from "../controls/filters/FilterLineCheckbox";
import FilterLineInput from "../controls/filters/FilterLineInput";
import PageArrows from "../controls/PageArrows";

class Recipes extends Component {
    static contextType = AppContext;
    filters;

    constructor(props) {
        super(props);
        this.state = {recipes: [], loading: true};
        this.filterValues = {
            pageCount: 10,
            page: 1
        };

        this.filters = [];

    }

    componentDidMount() {
        this.filters = [
            {
                code: 'type',
                name: 'Тип',
                options: {
                    multiple: true
                },
                vals: this.context.appData.type.reduce((prev, curr) => {
                    if (curr.parent) {
                        prev.push({'id': curr.id, 'name': curr.parent.name + ': ' + curr.name});
                    } else {
                        prev.push({'id': curr.id, 'name': curr.name});
                    }
                    return prev;
                }, [])
            }
        ]
        this.getRecipes();
        this.context.setTitle('Все рецепты');
    }

    filterChangeTimeout;

    handleFilterChange = (newValue, filterLine) => {
        this.filterValues[filterLine.code] = newValue;
        this.filterValues.page = 1;
        clearTimeout(this.filterChangeTimeout);
        this.filterChangeTimeout = setTimeout(() => {
            this.getRecipes()
        }, 200);
    }

    goToPage = (page) => {
        if (page < 1) {
            page = 1;
        }
        this.filterValues.page = page;
        this.getRecipes();
    }

    getRecipes = () => {
        this.context.obAxios.post(`/app/recipes`, this.filterValues).then(obResponse => {
            this.setState({recipes: obResponse, loading: false})
        })
    }

    deleteRecipe = (id) => {
        this.context.obAxios.post(`/app/recipes/delete`, {id: id}).then(obResponse => {
            if (obResponse.status) {
                this.getRecipes();
                this.context.displaySuccess(obResponse.message);
            } else {
                this.context.displayError(obResponse.message);
            }
        })
    }

    render() {
        const loading = this.state.loading;
        const filter = this.filterValues;
        const props = this.props;
        return (
            <Row>
                <Col xs={12} lg={2} className={'d-none d-md-block'}>
                    {
                        !props.hideAdd &&
                        <ListGroup variant={'flush'}>
                            <ListGroup.Item>
                                <Link to='/recipe/0/edit/'>
                                    <Button variant={"primary"}>Добавить рецепт</Button>
                                </Link>
                            </ListGroup.Item>
                        </ListGroup>
                    }
                    <FilterLineInput
                        filterValues={{name: 'Название'}}
                        obFilterChange={(filter) => this.handleFilterChange(filter, {'code': 'name'})}
                    />
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
                <Col xs={12} lg={10}>
                    {
                        loading ? <Loader/> :
                            <>
                                <PageArrows
                                    onPageChange={this.goToPage}
                                    page={filter.page}
                                />
                                {
                                    this.state.recipes.map((recipe, recipeIndex) =>
                                        <Row
                                            className={'border border-primary'}
                                            key={recipeIndex}
                                        >
                                            <Col xs={12} lg={8}>
                                                <Row>
                                                    <Col xs={12} lg={12}>
                                                        {
                                                            recipe.isMine &&
                                                            <PubStatusShow item={recipe}/>
                                                        }
                                                        {
                                                            typeof props.handleRecipeClick === 'function' &&
                                                            <Button
                                                                as={'a'}
                                                                variant={'text'}
                                                                onClick={() => props.handleRecipeClick(recipe)}
                                                            >
                                                                <h4>
                                                                    {recipe.name}
                                                                </h4>
                                                            </Button>
                                                        }
                                                        {
                                                            typeof props.handleRecipeClick !== 'function' &&
                                                            <Link to={recipe.viewLink}>
                                                                <h4>{recipe.name}</h4>
                                                            </Link>
                                                        }
                                                    </Col>
                                                    <Col xs={12} lg={12}>
                                                        <Row>
                                                            <Col xs={4}>
                                                                <TypeShow type={recipe.type}/>
                                                            </Col>
                                                            <Col xs={4}>
                                                                <TimeView recipe={recipe}/>
                                                            </Col>
                                                            <Col xs={4}>
                                                                <DifficultView value={recipe.difficult}/>
                                                            </Col>
                                                        </Row>
                                                    </Col>
                                                    <Col xs={12} className={'text-truncate'}>
                                                        {recipe.anounce}
                                                    </Col>
                                                </Row>
                                            </Col>
                                            <Col xs={12} lg={3}>
                                                {recipe.ingredients.map((ingredient, ingredientIndex) =>
                                                    <OneIngredient className={'recipe-list-ingredients'}
                                                                   ingredient={ingredient}
                                                                   key={ingredientIndex + "_" + recipeIndex}/>
                                                )}
                                            </Col>
                                            <Col className={'d-none d-lg-block'} lg={1}>
                                                {
                                                    recipe.canDelete &&
                                                    <Button
                                                        variant="danger"
                                                        size={'sm'}
                                                        onClick={(e) => this.deleteRecipe(recipe.id, e)}
                                                    >
                                                        <i className={'bi bi-trash'}/>
                                                    </Button>
                                                }
                                                {
                                                    typeof props.handleSelect === 'function' &&
                                                    <Button
                                                        variant="success"
                                                        size={'sm'}
                                                        onClick={() => props.handleSelect(recipe)}
                                                    >
                                                        Выбрать
                                                        <i className={'bi bi-plus'}/>
                                                    </Button>
                                                }
                                            </Col>
                                        </Row>
                                    )
                                }
                                <PageArrows
                                    onPageChange={this.goToPage}
                                    page={filter.page}
                                />
                            </>
                    }
                </Col>
            </Row>
        )
    }
}

export default Recipes;