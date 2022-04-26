import React, {Component} from 'react';
import AppContext from "../app-params";
import {Button, Col, Navbar, Row} from 'react-bootstrap';
import Loader from "../utils/Loader";
import {Link} from "react-router-dom";
import RecipeBlock from "../controls/recipes/RecipeBlock";

class RecipeView extends Component {
    static contextType = AppContext;

    constructor(props) {
        super(props);
        this.state = {recipe: {}, loading: true};
    }

    componentDidMount() {
        this.getRecipe();
    }

    returnToView = () => {
        this.props.history.push('/recipes');
    }

    getRecipe = () => {
        this.context.obAxios.get(`/app/recipe/` + this.props.match.params.id).then(obResponse => {
            if (this.context.app.checkNoError(obResponse)) {
                this.setState({recipe: obResponse, loading: false})
                this.context.setTitle('Рецепт - ' + obResponse.name);
            } else {
                this.props.history.push('/recipes');
            }
        })
    }

    copyRecipe = () => {
        this.context.obAxios.post(`/app/recipes/copy`, {id: this.props.match.params.id}).then(obResponse => {
            if (this.context.app.checkNoError(obResponse)) {
                if (obResponse.item && obResponse.item.id !== this.props.match.params.id) {
                    this.props.history.push(obResponse.item.editLink);
                }
            }
        })
    }

    sendPublishRequest = () => {
        this.context.obAxios.post(`/app/recipes/publish`, {id: this.props.match.params.id}).then(obResponse => {
            if (this.context.app.checkNoError(obResponse)) {
                this.setState({recipe: obResponse.item, loading: false})
                this.context.displaySuccess(obResponse.message);
            } else {
                this.context.displayError(obResponse.message);
            }
        })
    }

    render() {
        const loading = this.state.loading;
        const recipe = this.state.recipe;

        return (
            loading ? <Loader/> : (
                <Row>
                    <Col xs={12} lg={12}>
                        <Button
                            variant='link'
                            onClick={this.returnToView}
                            size={'sm'}
                        >Вернуться</Button>
                        <RecipeBlock recipe={recipe}/>
                    </Col>
                    <Navbar className={'w-100 buttons-navbar d-none d-md-flex'} bg={'dark'} variant={'dark'}>
                        {
                            recipe.canEdit &&
                            <Link to={recipe.editLink}>
                                <Button variant='success'>Редактировать</Button>
                            </Link>
                        }
                        <Button
                            variant='secondary'
                            className={'ml-3'}
                            onClick={this.copyRecipe}
                        >Копировать</Button>
                        {
                            recipe.canPublish &&
                            <Button
                                variant='primary'
                                className={'ml-auto'}
                                onClick={this.sendPublishRequest}
                            >Опубликовать</Button>
                        }
                    </Navbar>
                </Row>
            )
        )
    }
}

export default RecipeView;