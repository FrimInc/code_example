import React, {Component} from "react";
import AppContext from "../app-params";
import {Button, Form, Modal} from "react-bootstrap";
import {RiMenuAddLine} from "react-icons/all";

class AddSomeValue extends Component {

    static contextType = AppContext;

    constructor(props) {
        super(props);
        this.state = {
            value: '',
            modal_open: false
        }

    }

    handleOpen = () => {
        this.setState({modal_open: true});
    }

    handleClose = () => {
        this.setState({modal_open: false});
    }

    handleOk = () => {
        this.props.handleAdd(this.state.value);
        this.handleClose();
    }

    handleInputChange = (e) => {
        this.setState({
            value: e.target.value
        });
    }

    render() {
        return (
            <>
                <Button className={this.props.className} variant="outline-secondary" onClick={this.handleOpen}>
                    <RiMenuAddLine/>
                </Button>
                {this.state.modal_open &&
                <Modal show={true} onHide={this.handleClose}>
                    <Modal.Body>
                        <Form.Group controlId="recipeName">
                            <Form.Label>{this.props.label}</Form.Label>
                            <Form.Control
                                type="text"
                                name="name"
                                value={this.state.value}
                                onChange={this.handleInputChange}
                            />
                        </Form.Group>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant={'primary'} onClick={this.handleOk}>Добавить</Button>
                        <Button variant="info" onClick={this.handleClose}>
                            Закрыть
                        </Button>
                    </Modal.Footer>
                </Modal>
                }
            </>
        )
    }

}

export default AddSomeValue;