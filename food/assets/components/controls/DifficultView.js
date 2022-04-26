import React, {Component} from "react";
import {GiCook} from "react-icons/all";

class DifficultView extends Component {
    render() {
        let stars = [];
        for (let i = 0; i < this.props.value; i++) {
            stars.push(<GiCook key={i}/>)
        }
        return stars;
    }
}

export default DifficultView;