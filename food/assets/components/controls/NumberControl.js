import {Form} from "react-bootstrap";
import React, {Component} from 'react';


class NumberControl extends Component {
    render() {

        const cStep = Math.min(1000, Math.max(this.props.step * Math.pow(3, this.props.value.length - 2), this.props.step));

        return (
            <Form.Control
                type={'number'}
                value={this.props.value}
                step={cStep ? cStep : this.props.step}
                min={this.props.step}
                onChange={this.props.onChange}
            />
        )
    }
}

export default NumberControl;