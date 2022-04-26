import React from "react";
import {Form} from "react-bootstrap";
import FilterLine from "./FilterLine";


class FilterLineCheckbox extends FilterLine {
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
        let newFilter;

        if (this.props.filterValues.options.multiple === true) {
            newFilter = this.state.values
        } else {
            newFilter = [];
        }

        if (e.target.checked) {
            newFilter.push(e.target.value);
        } else {
            const delValue = e.target.value;
            newFilter = newFilter.filter((value) => {
                return value !== delValue;
            });
        }

        newFilter = newFilter.filter((value, index, self) => {
            return self.findIndex(item => String(item) === String(value)) === index;
        });

        this.setState({
            values: newFilter
        }, this.handleChangeWithTimeout);
    }

    render() {
        const filterValues = this.props.filterValues;
        const filter = this.state.values;

        return (
            <Form>
                <Form.Group>
                    <Form.Label>{filterValues.name}</Form.Label>
                    {
                        filterValues.vals.map(keyValuePair =>
                            (
                                filter.findIndex(item => String(item) === String(keyValuePair.id)) > -1
                                ||
                                filter.length <= 5
                            )
                            &&
                            <Form.Check
                                key={keyValuePair.id}
                                onChange={this.handleChange}
                                type={filterValues.options.multiple ? 'checkbox' : 'radio'}
                                checked={filter.findIndex(item => String(item) === String(keyValuePair.id)) > -1}
                                id={'filter_' + filterValues.code + keyValuePair.id}
                                value={keyValuePair.id}
                                label={keyValuePair.name}
                            />
                        )
                    }
                    {
                        filter.length > 5 &&
                        filterValues.vals.map(keyValuePair =>
                            filter.findIndex(item => String(item) === String(keyValuePair.id)) === -1 &&
                            <Form.Check
                                key={keyValuePair.id}
                                onChange={this.handleChange}
                                type={filterValues.options.multiple ? 'checkbox' : 'radio'}
                                checked={filter.findIndex(item => String(item) === String(keyValuePair.id)) > -1}
                                id={'filter_' + filterValues.code + keyValuePair.id}
                                value={keyValuePair.id}
                                label={R}
                            />
                        )
                    }
                </Form.Group>
            </Form>
        )
    }
}

export default FilterLineCheckbox;