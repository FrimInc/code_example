import {Button, Modal} from "react-bootstrap";
import React from "react";
import axios from "axios";
import {GiMeal} from "react-icons/all";
import Recipes from "../../pages/Recipes";


class RecipeSelector extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            search: '',
            modal_open: false,
            recipes: [],
        }
    }

    handleOpen = () => {
        this.setState({modal_open: true});
    }

    handleClose = () => {
        this.setState({modal_open: false});
    }

    getRecipes = () => {
        axios.post(`/app/autocomplete/recipe`, {
            search: this.state.search
        }).then(obResponse => {
            this.setState({recipes: obResponse.data});
        })
    }

    handleRecipeChoose = (recipe) => {
        this.props.handleClose(recipe);
        this.handleClose();
    }

    handleSearchChange = (e) => {
        this.setState({search: e.target.value}, () => {
            this.getRecipes();
        });
    }

    render() {
        return (
            <>
                <Button size="sm" variant='primary' className={this.props.className} onClick={this.handleOpen}>
                    <GiMeal/>
                </Button>
                {
                    this.state.modal_open &&
                    <Modal
                        show={true}
                        onHide={this.handleClose}
                        dialogClassName={'width-90pp'}
                    >
                        <Modal.Header closeButton>
                            <Modal.Title>Добавление рецепта в меню</Modal.Title>
                        </Modal.Header>
                        <Modal.Body>
                            <Recipes
                                hideAdd={true}
                                handleSelect={this.handleRecipeChoose}
                                handleRecipeClick={this.props.handleRecipeClick}
                            />
                        </Modal.Body>
                        <Modal.Footer>
                            <Button variant="secondary" onClick={this.handleClose}>
                                Закрыть
                            </Button>
                        </Modal.Footer>
                    </Modal>
                }
            </>
        )
    }
}

export default RecipeSelector;