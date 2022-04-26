import React, {Component} from 'react';
import {Button, Nav} from "react-bootstrap";
import AppContext from "../app-params";
import axios from "axios";

class UserLine extends Component { //@TODO переделать

    static contextType = AppContext;

    constructor(props) {
        super(props);
        this.state = {
            values: {}
        }
    }

    makeLogout = () => {
        axios.post('/logout', this.state.values).then(() => {
            this.context.app.getAppData();
        })
    }

    render() {
        return (
            <Nav>
                <Nav.Item>
                    <Button variant={'light'}>{this.props.user.fullName}</Button>
                </Nav.Item>
                <Nav.Item>
                    <Button variant={'link'} onClick={this.makeLogout}>Выйти</Button>
                </Nav.Item>
            </Nav>
        )
    }
}

export default UserLine;