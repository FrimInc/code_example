import React, {Component} from 'react';
import {Button, Modal} from 'react-bootstrap';
import IngredientSelector from "./IngredientSelector";
import {FaAppleAlt} from "react-icons/all";

class IngredientMenuSelector extends Component {

    constructor(props) {
        super(props);
        this.state = {
            openSelector: false
        };
    }

    setOpenSelector = (value) => {
        this.setState({openSelector: value});
    }

    handleSelect = (ingredient) => {
        this.props.onSelect(ingredient);
        this.setOpenSelector(false)
    }

    render() {

        return (
            <>
                {
                    !this.state.openSelector &&
                    <Button
                        className={this.props.className}
                        variant={'secondary'}
                        size={'sm'}
                        onClick={() => this.setOpenSelector(true)}
                    >
                        <FaAppleAlt/>
                    </Button>
                }
                {
                    this.state.openSelector &&
                    <Modal
                        show={true}
                        onHide={() => this.setOpenSelector(false)}
                        dialogClassName={'width-90pp'}
                    >
                        <Modal.Header closeButton>
                            <Modal.Title>Добавление простого блюда</Modal.Title>
                        </Modal.Header>
                        <Modal.Body>
                            <IngredientSelector onSave={this.handleSelect}/>
                        </Modal.Body>
                        <Modal.Footer>
                            <Button variant="secondary" onClick={() => this.setOpenSelector(false)}>
                                Закрыть
                            </Button>
                        </Modal.Footer>
                    </Modal>
                }

            </>
        )
    }

}

export default IngredientMenuSelector;