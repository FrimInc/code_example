import React, {Component} from "react";
import {Spinner} from "react-bootstrap";

class Loader extends Component {
    render() {
        return (
            <Spinner animation="border" role="status">
                <span className="sr-only">Загружаем</span>
            </Spinner>
        )
    }
}

export default Loader;