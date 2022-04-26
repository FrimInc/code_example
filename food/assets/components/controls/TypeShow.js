import {Badge} from "react-bootstrap";
import React from "react";

class TypeShow extends React.Component {
    render() {
        return (
            <Badge variant="dark">
                <i>{this.props.type.parent ? this.props.type.parent.name + ' / ' : ''} {this.props.type.name}</i>
            </Badge>
        )
    }
}

export default TypeShow;