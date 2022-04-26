import React, {Component} from 'react';
import AppContext from "../app-params";
import {
    Badge,
    Button, Card,
    Col,
    Form,
    InputGroup, ListGroup,
    Modal, Navbar,
    Overlay,
    Popover,
    Row
} from 'react-bootstrap';
import Loader from "../utils/Loader";
import RecipeSelector from "../controls/recipes/RecipeSelector";
import {Link} from "react-router-dom";
import TypeShow from "../controls/TypeShow";
import IngredientMenuSelector from "../controls/ingredients/IngredientMenuSelector";

import Draggable from 'react-draggable';
import AmountSelector from "../controls/AmountSelector";
import OneIngredient from "../controls/ingredients/OneIngredient";
import AddSomeValue from "../controls/AddSomeValue";
import RecipeBlock from "../controls/recipes/RecipeBlock";
import {FiPrinter, GiMeal, RiCupFill} from "react-icons/all";
import TimeView from "../controls/TimeView";

class MenuEdit extends Component {
    static contextType = AppContext;
    validated;

    hovDayTo = null;
    hovMealTo = null;

    hovDayFrom = null;
    hovMealFrom = null;

    constructor(props) {
        super(props);
        this.state = {
            values: {},
            recipesShow: false,
            dragging: false,
            loading: true,
            printTooltipShow: false,
            printTooltipShowTarget: false,
            validated: false,
            visibleRecipe: false,
            visibleRecipeModalOpened: false
        };

        this.form = React.createRef();
    }

    componentDidMount = () => {
        this.getMenu();
    }

    setMenu = (menu) => {
        this.setState({values: menu, loading: false})
        this.context.setTitle('Меню - ' + menu.name);
        this.checkValidation();
    }

    getMenu = () => {
        this.context.obAxios.get(`/app/menu/` + this.props.id).then(obResponse => {
            if (this.context.app.checkNoError(obResponse)) {
                this.setMenu(obResponse);
            } else {
                this.props.history.push('/menus')
            }
        })
    }

    handleInputChange = (e) => {
        this.checkValidation();
        this.setState({
            values: {...this.state.values, [e.target.name]: e.target.value}
        });
    }

    handleMealNameChange = (e) => {
        if (this.checkValidation()) {

            let newDays = {};

            this.state.values.week.days.map((day, dayNum) => {
                let newDay = {'meals': {}};

                Object.entries(day.meals).map(obMeal => {
                    if (e.target.name === obMeal[0]) {
                        obMeal[0] = e.target.value;
                    }
                    newDay['meals'][obMeal[0]] = obMeal[1];
                });

                newDays[dayNum] = newDay;
            });

            this.state.values.week.days = newDays;

            let newMeals = [];
            this.state.values.meals.map(mealName => {
                if (mealName === e.target.name) {
                    mealName = e.target.value;
                }
                newMeals.push(mealName);
            });

            this.state.values.meals = newMeals;

            this.setState({
                values: {...this.state.values, 'week': this.state.values.week}
            });
        }
    }

    submitWithTimeout = () => {
        clearTimeout(this.submitTimeout);
        this.submitTimeout = setTimeout(this.submitForm, 1000, false, 'check');
    }

    submitForm = (event, send = 'check') => {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        if (this.checkValidation()) {
            this.context.obAxios.post('/app/menus/' + send, this.state.values).then(obResponse => {
                if (obResponse.status) {
                    if (send !== 'check') {
                        if (obResponse.item.id) {
                            this.props.history.push('/menu/' + obResponse.item.id);
                        }
                    } else if (this.state.values.id !== obResponse.item.id) {
                        this.props.history.push('/menu/' + obResponse.item.id + '/edit');
                    }
                    this.setMenu(obResponse.item);
                    this.context.displaySuccess(obResponse.message);
                } else {
                    this.context.displayError(obResponse.message);
                }
            })
        }
    }

    sendPublishRequest = () => { //@TODO вынести в компонент публикации универсальный
        if (this.checkValidation()) {
            this.context.obAxios.post(`/app/menus/publish`, {id: this.props.id}).then(obResponse => {
                if (this.context.app.checkNoError(obResponse)) {
                    this.setMenu(obResponse.item);
                    this.context.displaySuccess(obResponse.message);
                } else {
                    this.context.displayError(obResponse.message);
                }
            })
        }
    }

    createShopList = (event) => {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        if (this.checkValidation()) {
            this.context.obAxios.post('/app/shopLists/createFromMenu', this.state.values).then(obResponse => {
                if (obResponse.status) {
                    this.props.history.push('/shopList/' + obResponse.item.id);
                    this.context.displaySuccess(obResponse.message);
                } else {
                    this.context.displayError(obResponse.message);
                }

            })
        }
    }

    addToMainShopList = (event) => {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        if (this.checkValidation()) {
            this.context.obAxios.post('/app/shopLists/addToMainFromMenu', this.state.values).then(obResponse => {
                if (obResponse.status) {
                    this.props.history.push('/shopList/' + obResponse.item.id);
                    this.context.displaySuccess(obResponse.message);
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
        } else {
            this.setState({validated: false});
        }
        return validity;
    }

    handleRecipeChoose = (dayNum, mealName, recipe, isDelete = false) => {
        if (isDelete) {
            this.state.values.week.days[dayNum].meals[mealName].recipes[recipe.id] = false;
        } else {
            this.state.values.week.days[dayNum].meals[mealName].recipes[recipe.id] = {
                recipe: recipe,
                amount: this.state.values.week.amount
            };
        }
        this.setState({
            values: {...this.state.values, 'week': this.state.values.week}
        }, () => {
            this.submitForm(false, 'check')
        });
    }

    handleMealAdd = (newMealName, mealIndex) => {
        if (newMealName) {
            let newDays = [];

            this.state.values.week.days.map((day, dayNum) => {
                let newDay = {'meals': {}};

                Object.entries(day.meals).map((obMeal, mealIndexCheck) => {
                    if (mealIndex === mealIndexCheck) {
                        newDay['meals'][newMealName] = {
                            "recipes": [],
                            "ingredients": []
                        };
                    }
                    newDay['meals'][obMeal[0]] = obMeal[1];
                });

                newDays[dayNum] = newDay;
            });

            this.state.values.week.days = newDays;

            let newMeals = [];
            this.state.values.meals.map((mealName, mealIndexCheck) => {
                if (mealIndex === mealIndexCheck) {
                    newMeals.push(newMealName);
                }
                newMeals.push(mealName);
            });

            this.state.values.meals = newMeals;

            this.setState({
                values: {...this.state.values, 'week': this.state.values.week}
            });
        }
    }

    handleMealDelete = (deleteMealIndex, deleteMealName) => {

        if (!confirm('Удалить ' + deleteMealName + '?')) {
            return;
        }

        let newDays = [];

        this.state.values.week.days.map((day, dayNum) => {
            let newDay = {'meals': {}};

            Object.entries(day.meals).map((obMeal, mealIndexCheck) => {
                if (deleteMealIndex !== mealIndexCheck) {
                    newDay['meals'][obMeal[0]] = obMeal[1];
                }

            });

            newDays[dayNum] = newDay;
        });

        this.state.values.week.days = newDays;

        let newMeals = [];

        this.state.values.meals.map((mealName, mealIndexCheck) => {
            if (deleteMealIndex !== mealIndexCheck) {
                newMeals.push(mealName);
            }
        });

        this.state.values.meals = newMeals;

        this.setState({
            values: {...this.state.values, 'week': this.state.values.week}
        });

    }

    handleIngredientChoose = (dayNum, mealName, ingredient, isDelete = false) => {

        if (isDelete) {
            this.state.values.week.days[dayNum].meals[mealName].ingredients[ingredient.id] = false;
        } else {
            this.state.values.week.days[dayNum].meals[mealName].ingredients[ingredient.id] = {
                ingredient: ingredient,
                amount: this.state.values.week.amount * ingredient.minimum
            };
        }

        this.setState({
            values: {...this.state.values, 'week': this.state.values.week}
        }, () => {
            this.submitForm(false, 'check')
        });
    }

    handleDishAmountChange = (dayNum, mealName, mealType, rowId, e) => {
        this.state.values.week.days[dayNum].meals[mealName][mealType][rowId].amount = (typeof e == 'object' ? e.target.value : e) * 1;
        this.setState({
            values: {...this.state.values, 'week': this.state.values.week}
        }, this.submitWithTimeout);
    }

    handleWeekAmountChange = (e) => {
        if (this.checkValidation()) {
            this.state.values.week.amount = e.target.value;
            this.setState({
                values: {...this.state.values, 'week': this.state.values.week}
            }, this.submitWithTimeout);
        }
    }

    handleHoverTo = (e, rel_day, rel_meal) => {
        if (
            rel_day !== this.hovDayFrom
            || rel_meal !== this.hovMealFrom
        ) {
            this.hovDayTo = rel_day;
            this.hovMealTo = rel_meal;
        }
    }

    handleHoverFrom = (e, rel_day, rel_meal) => {
        this.setState({dragging: true});
        this.hovDayFrom = rel_day;
        this.hovMealFrom = rel_meal;
    }

    handleDragStop = (e, data, dish, type) => {
        if (
            this.hovDayTo !== null
            && this.hovMealTo !== null
            && this.hovDayFrom !== null
            && this.hovMealFrom !== null
            && (
                this.hovDayTo !== this.hovDayFrom
                || this.hovMealTo !== this.hovMealFrom
            )
        ) {
            this.state.values.week.days[this.hovDayFrom].meals[this.hovMealFrom][type + 's'][dish[type].id].position = {
                x: data.x,
                y: data.y
            };

            this.setState({
                values: {...this.state.values, 'week': this.state.values.week}
            });

            if (!e.ctrlKey) {
                this.state.values.week.days[this.hovDayFrom].meals[this.hovMealFrom][type + 's'][dish[type].id] = false;
            }

            this.state.values.week.days[this.hovDayTo].meals[this.hovMealTo][type + 's'][dish[type].id] = dish;
            this.state.values.week.days[this.hovDayTo].meals[this.hovMealTo][type + 's'][dish[type].id].position = false;

            setTimeout(() => {
                this.setState({
                    dragging: false,
                    values: {...this.state.values, 'week': this.state.values.week}
                }, () => {
                    this.submitForm(false, 'check')
                });
            }, 2);
        }

        setTimeout(() => {
            this.hovDayTo = null
            this.hovMealTo = null
            this.hovDayFrom = null
            this.hovMealFrom = null
            this.setState({
                dragging: false
            });
        }, 15);


    }

    getDishRecipe = (dayNum, mealName, dish) => {
        const props = this.props;

        return (
            <Draggable
                defaultPosition={{x: 0, y: 0}}
                position={dish.position ? dish.position : {x: 0, y: 0}}
                handle=".drag-handler"
                key={mealName + '_' + dish.recipe.id}
                onStop={(e, data) => this.handleDragStop(e, data, dish, 'recipe')}
            >
                <Card className={'mt-1'}>
                    <Card.Header
                        className={'pr-3'}
                    >
                        {
                            props.editing &&
                            <i
                                className={'bi bi-arrows-move drag-handler'}
                                onMouseDown={(e) => this.handleHoverFrom(e, dayNum, mealName)}
                            >&nbsp;</i>
                        }
                        {
                            props.editing &&
                            <Button
                                variant="link"
                                className={'text-danger delete-button'}
                                size={'sm'}
                                onClick={() => this.handleRecipeChoose(dayNum, mealName, dish.recipe, true)}
                            >
                                <i className={'bi bi-trash'}/>
                            </Button>
                        }
                        <div>
                            <TypeShow type={dish.recipe.type}/>
                        </div>
                        {
                            !props.editing &&
                            <Button variant={'light'} onClick={() => this.setVisibleRecipe(dish.recipe)}>
                                <b>{dish.recipe.name}</b>
                            </Button>
                        }
                        {
                            props.editing &&
                            dish.recipe.name
                        }
                    </Card.Header>
                    <Card.Body>
                        <Card.Text>
                            <TimeView recipe={dish.recipe}/>
                        </Card.Text>
                    </Card.Body>
                    <Card.Footer>
                        {
                            props.editing &&
                            <AmountSelector
                                value={dish.amount}
                                step={1}
                                min={1}
                                counts={[1, 2]}
                                append={
                                    <>
                                        <GiMeal/>&nbsp;{dish.amount}
                                    </>
                                }
                                onChange={(e) => this.handleDishAmountChange(dayNum, mealName, 'recipes', dish.recipe.id, e)}
                            />
                        }
                        {
                            !props.editing &&
                            <>
                                <GiMeal/>&nbsp;{dish.amount}
                            </>

                        }
                    </Card.Footer>
                </Card>
            </Draggable>
        )
    }

    getDishIngredient = (dayNum, mealName, dish) => {
        const props = this.props;

        return (
            <Draggable
                defaultPosition={{x: 0, y: 0}}
                position={{x: 0, y: 0}}
                handle=".drag-handler"
                key={mealName + '_' + dish.ingredient.id}
                onStop={(e, data) => this.handleDragStop(e, data, dish, 'ingredient')}
            >
                <Card key={mealName + '_' + (dish.recipe ? dish.recipe.id : dish.ingredient.name)} className={'mt-1'}>
                    <Card.Header
                        className={'pr-0'}
                    >
                        {
                            props.editing &&
                            <i
                                className={'bi bi-arrows-move drag-handler'}
                                onMouseDown={(e) => this.handleHoverFrom(e, dayNum, mealName)}
                            >&nbsp;</i>
                        }
                        {
                            props.editing &&
                            <Button
                                variant="link"
                                className={'text-danger delete-button'}
                                size={'sm'}
                                onClick={() => this.handleIngredientChoose(dayNum, mealName, dish.ingredient, true)}
                            >
                                <i className={'bi bi-trash'}/>
                            </Button>
                        }
                        <Row>
                            <Col xs={12}>
                                <Badge variant="dark">
                                    {dish.ingredient.type.name}
                                </Badge>
                            </Col>
                            <Col xs={12}>
                                {
                                    !props.editing &&
                                    <Button variant={'light'}>
                                        <b>{dish.ingredient.name}</b>
                                    </Button>
                                }
                                {
                                    props.editing &&
                                    dish.ingredient.name
                                }
                            </Col>
                        </Row>
                    </Card.Header>
                    <Card.Footer>
                        {
                            props.editing &&
                            <AmountSelector
                                value={dish.amount}
                                step={dish.ingredient.minimum}
                                min={dish.ingredient.minimum}
                                append={
                                    <>
                                        <RiCupFill/>&nbsp;{dish.ingredient.units.short}
                                    </>
                                }
                                counts={[dish.ingredient.units.step * 10, dish.ingredient.units.step * 100]}
                                onChange={(e) => this.handleDishAmountChange(dayNum, mealName, 'ingredients', dish.ingredient.id, e)}
                            />
                        }
                        {
                            !props.editing &&
                            <>
                                <RiCupFill/>&nbsp;{dish.amount + ' ' + dish.ingredient.units.short}
                            </>

                        }
                    </Card.Footer>

                </Card>
            </Draggable>
        )
    }

    setVisibleRecipe = (recipe) => {
        this.setState({
            visibleRecipe: recipe,
            visibleRecipeModalOpened: true
        });
    }

    makePrint = (recipes) => {
        window.onafterprint = () => {
            this.setState({
                recipesShow: false,
                printTooltipShow: false
            });
        }
        this.setState({recipesShow: recipes}, () => window.print());
    }

    setPrintTooltipShow = (e) => {
        this.setState({
            printTooltipShow: !this.state.printTooltipShow,
            printTooltipShowTarget: e.target
        });
    }

    printBlock = () => {
        //@TODO добавить кнопку "Напечатать с учетом количетсва моих порций"
        return (
            <span className={'print-block print-hidden d-none d-md-inline'}>
                <Overlay
                    placement={'bottom'}
                    show={this.state.printTooltipShow}
                    target={this.state.printTooltipShowTarget}
                >
                    <Popover
                        id="popover-print"
                        className={'print-hidden'}
                    >
                        <Popover.Title>
                            Напечатать
                        </Popover.Title>
                        <Popover.Content>
                            <Button onClick={() => this.makePrint(false)}>Только меню</Button>
                            <Button onClick={() => this.makePrint(true)}>Меню и рецепты</Button>
                        </Popover.Content>
                    </Popover>
                </Overlay>
                <FiPrinter onClick={this.setPrintTooltipShow}/>
            </span>
        );
    }

    render() {

        const loading = this.state.loading;
        const props = this.props;
        const validated = this.state.validated;
        const dragging = this.state.dragging;
        const recipesShow = this.state.recipesShow;
        const visibleRecipe = this.state.visibleRecipe;
        const visibleRecipeModalOpened = this.state.visibleRecipeModalOpened;

        const values = this.state.values;
        const meals_xs = typeof values.meals != 'undefined' ? Math.floor(12 / values.meals.length) : 3;

        let Recipes = {};

        return (
            loading ? <Loader/> : (
                <>
                    <Modal
                        show={visibleRecipeModalOpened}
                        dialogClassName={'width-90pp'}
                        onHide={() => this.setState({visibleRecipeModalOpened: false})}
                    >
                        <Modal.Header closeButton>
                            <Modal.Title>{visibleRecipe.name}</Modal.Title>
                        </Modal.Header>
                        <Modal.Body>
                            <RecipeBlock noname={true} recipe={visibleRecipe}/>
                        </Modal.Body>
                        <Modal.Footer>
                            <Button
                                variant="secondary"
                                onClick={() => this.setState({visibleRecipeModalOpened: false})}
                            >
                                Закрыть
                            </Button>
                        </Modal.Footer>
                    </Modal>
                    <Form noValidate validated={validated} onSubmit={(e) => this.submitForm(e, 'add')}
                          ref={form => this.form.current = form}>
                        <Row>
                            <Col xs={12} md={props.day === undefined ? 10 : 12} className={'print-100 print-ml-25'}>
                                {
                                    props.editing &&
                                    <>
                                        <Form.Group as={Row} controlId="menuName">
                                            <Form.Label column sm={'3'}>Название меню</Form.Label>
                                            <Col sm="9">
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
                                                    Краткое и понятное название меню, например <i>Летнее меню</i>
                                                </Form.Text>
                                            </Col>
                                        </Form.Group>
                                        <Form.Group as={Row} controlId="menuPortions">
                                            <Form.Label column sm={'3'}>Порций по умолчанию</Form.Label>
                                            <Col sm="2">
                                                <Form.Control
                                                    required
                                                    type="number"
                                                    name="amount"
                                                    placeholder=""
                                                    value={values.week.amount}
                                                    onChange={this.handleWeekAmountChange}
                                                />
                                            </Col>
                                        </Form.Group>
                                    </>
                                    ||
                                    <h2>
                                        {
                                            props.day === undefined ?
                                                <>
                                                    {values.name}
                                                    {this.printBlock()}
                                                </>
                                                :
                                                <>
                                                    <Link to={values.viewLink} className='nav-link'>
                                                        {values.name}
                                                    </Link>
                                                </>
                                        }
                                    </h2>
                                }
                                <Row className={'d-none d-md-flex pl-4'}>
                                    {
                                        values.meals.map((meal_name, mealIndex) =>
                                            <Col className={'border-left'} key={mealIndex} xs={meals_xs}>
                                                {
                                                    props.editing &&
                                                    <InputGroup>
                                                        {
                                                            mealIndex === 0 &&
                                                            <InputGroup.Prepend>
                                                                <AddSomeValue
                                                                    label={'Прием пищи'}
                                                                    handleAdd={(mealName) => this.handleMealAdd(mealName, 0)}
                                                                />
                                                            </InputGroup.Prepend>
                                                        }
                                                        <Form.Control
                                                            required
                                                            type="text"
                                                            name={meal_name}
                                                            value={meal_name}
                                                            onChange={this.handleMealNameChange}
                                                        />
                                                        <InputGroup.Append>
                                                            <AddSomeValue
                                                                className={'float-right'}
                                                                label={'Прием пищи'}
                                                                handleAdd={(mealName) => this.handleMealAdd(mealName, mealIndex + 1)}
                                                            />
                                                            <Button
                                                                variant="link"
                                                                className={'text-danger delete-button'}
                                                                size={'sm'}
                                                                onClick={() => this.handleMealDelete(mealIndex, meal_name)}
                                                            >
                                                                <i className={'bi bi-trash'}/>
                                                            </Button>
                                                        </InputGroup.Append>
                                                    </InputGroup>
                                                }
                                                {
                                                    !props.editing &&
                                                    meal_name
                                                }
                                            </Col>
                                        )
                                    }
                                </Row>
                                {
                                    [
                                        'Пн',
                                        'Вт',
                                        'Ср',
                                        'Чт',
                                        'Пт',
                                        'Сб',
                                        'Вс'
                                    ].map((day, dayNum) =>
                                        (props.day === undefined || dayNum === props.day)
                                        &&
                                        <Row key={dayNum} className={'page-break-avoid pl-md-4'}>
                                            <div className={'menu-dayName d-none d-md-block'}>
                                                {day}
                                            </div>
                                            {
                                                values.meals.map((mealName) =>
                                                    <Col
                                                        className={'border-left border-top meal cell px-1 ' + (dragging ? 'dragging' : '')}
                                                        key={mealName}
                                                        xs={12}
                                                        md={meals_xs}
                                                    >
                                                        <div className={'d-block d-md-none'}>
                                                            {day}/{mealName}
                                                        </div>
                                                        {
                                                            dragging &&
                                                            <div
                                                                className={'drag-target'}
                                                                onMouseMove={(e) => this.handleHoverTo(e, dayNum, mealName)}
                                                            >&nbsp;</div>
                                                        }
                                                        {
                                                            Object.values(values.week.days[dayNum].meals[mealName].recipes).map((dish) =>
                                                                dish &&
                                                                this.getDishRecipe(dayNum, mealName, dish)
                                                            )
                                                        }
                                                        {
                                                            Object.values(values.week.days[dayNum].meals[mealName].ingredients).map((dish) =>
                                                                dish &&
                                                                this.getDishIngredient(dayNum, mealName, dish)
                                                            )
                                                        }
                                                        {
                                                            props.editing &&
                                                            <div className={'ingredient-recipe-add'}>
                                                                <RecipeSelector
                                                                    className={'float-right opacity-03 ml-2 mr-1'}
                                                                    handleRecipeClick={(recipe) => this.setVisibleRecipe(recipe)}
                                                                    handleClose={(recipe) => this.handleRecipeChoose(dayNum, mealName, recipe)}
                                                                />
                                                                <IngredientMenuSelector
                                                                    className={'float-right opacity-03'}
                                                                    onSelect={(ingredient) => this.handleIngredientChoose(dayNum, mealName, ingredient)}/>
                                                            </div>
                                                        }
                                                    </Col>
                                                )
                                            }
                                        </Row>
                                    )
                                }
                                {
                                    recipesShow &&
                                    [
                                        'Пн',
                                        'Вт',
                                        'Ср',
                                        'Чт',
                                        'Пт',
                                        'Сб',
                                        'Вс'
                                    ].map((day, dayNum) =>
                                        values.meals.map((mealName) =>
                                            Object.values(values.week.days[dayNum].meals[mealName].recipes).map((dish) => {
                                                    if (!Recipes[dish.recipe.id]) {
                                                        Recipes[dish.recipe.id] = true;
                                                        return (
                                                            <div
                                                                className={'page-break-before'}
                                                                key={dish.recipe.id}
                                                            >
                                                                <span>Это исходный рецепт, без учета Вашего количества порций</span>
                                                                <RecipeBlock recipe={dish.recipe}/>
                                                            </div>
                                                        )
                                                    } else {
                                                        return '';
                                                    }
                                                }
                                            )))
                                }
                            </Col>
                            {
                                props.day === undefined &&
                                <Col md={2} className={'print-hidden'}>
                                    <h4>Ингредиенты</h4>
                                    <ListGroup variant={'flush'}>
                                        {
                                            !props.editing &&
                                            <>
                                                {
                                                    this.context.appData.hasMainShopList &&
                                                    <ListGroup.Item>
                                                        <Button
                                                            variant={'success'}
                                                            className={'d-none d-md-block'}
                                                            onClick={this.addToMainShopList}
                                                        >
                                                            Добавить в основной список
                                                        </Button>
                                                    </ListGroup.Item>
                                                }
                                                <ListGroup.Item>
                                                    <Button
                                                        variant={'secondary'}
                                                        size={'sm'}
                                                        className={'d-none d-md-block'}
                                                        onClick={this.createShopList}
                                                    >
                                                        Создать новый список покупок
                                                    </Button>
                                                </ListGroup.Item>
                                            </>
                                        }
                                        <ListGroup.Item>
                                            {
                                                values.ingredients.map((ingredient, ingredientIndex) =>
                                                    <OneIngredient ingredient={ingredient} key={ingredientIndex}/>
                                                )
                                            }
                                        </ListGroup.Item>
                                    </ListGroup>
                                </Col>
                            }
                            {
                                props.day === undefined &&
                                <Col xs={12} md={12} className={'print-hidden d-none d-md-block'}>
                                    <Navbar className={'w-100 buttons-navbar'} bg={'dark'} variant={'dark'}>
                                        {
                                            !props.editing &&
                                            <>
                                                {
                                                    values.canEdit &&
                                                    <Link to={values.editLink}>
                                                        <Button variant='success'>Редактировать</Button>
                                                    </Link>
                                                }
                                                <Button
                                                    variant='secondary'
                                                    className={'ml-3'}
                                                    onClick={() => this.submitForm(false, 'copy')}
                                                >Копировать</Button>
                                                {
                                                    values.canPublish &&
                                                    <Button variant='primary' className={'ml-auto'}
                                                            onClick={this.sendPublishRequest}>Опубликовать</Button>

                                                }
                                            </>
                                        }
                                        {
                                            props.editing &&
                                            <Button type="submit" variant='success'>Сохранить</Button>
                                        }
                                    </Navbar>
                                </Col>
                            }
                        </Row>
                    </Form>
                </>
            )
        )
    }

}

export default MenuEdit;