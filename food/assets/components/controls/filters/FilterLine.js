import React from "react";
import qs from "query-string";
import {Form} from "react-bootstrap";


class FilterLine extends React.Component {
    constructor(props) {
        super(props);
        this.state = {values: []};
    }

    componentDidMount() {
        const obQuery = qs.parse(window.location.search, {arrayFormat: 'comma'});
        let newFilter = [];
        for (let paramKey in obQuery) {
            let filterFieldName = paramKey.replace('filter_', '');
            if (
                paramKey.search('filter_') === 0
                && this.props.filterValues.code === filterFieldName
            ) {
                newFilter = obQuery[paramKey];
                break;
            }
        }
        this.setState({values: newFilter});
    }


    render() {
        return '';
    }
}

export default FilterLine;