import React from "react";
import {Form} from "react-bootstrap";
import FilterLine from "./FilterLine";


class FilterLineInput extends FilterLine {
    constructor(props) {
        super(props);
        this.state = {values: []};
    }

    handleChangeWithTimeout = () => {
        clearTimeout(this.submitTimeout);
        this.submitTimeout = setTimeout(this.pushFilterChange, 10);
    }

    pushFilterChange = () => {
        this.props.obFilterChange(this.state.values);
    }

    handleChange = (e) => {
        this.setState({
            values: e.target.value
        }, this.handleChangeWithTimeout);
    }

    render() {
        const filterValues = this.props.filterValues;
        const filter = this.state.values;

        return (
            <Form>
                <Form.Group>
                    <Form.Control
                        required
                        type="text"
                        name="name"
                        placeholder={filterValues.name}
                        value={filter}
                        onChange={this.handleChange}
                    />
                </Form.Group>
            </Form>
        )
    }
}

export default FilterLineInput;