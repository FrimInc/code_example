import React, {Component} from "react";
import AppContext from '../app-params';
import {Form} from "react-bootstrap";

class HtmlSelect extends Component {
    static contextType = AppContext;

    render() {
        return (
            <AppContext.Consumer>
                {
                    appData => (
                        <Form.Control
                            as="select"
                            size="lg"
                            custom
                            name={this.props.name}
                            value={this.props.current_value}
                            onChange={this.props.onChange}
                        >
                            {
                                appData.appData[this.props.name].map(item =>
                                    <option key={item.id}
                                            value={item.id}>{item.parent ? item.parent.name + ' / ' : ''}{item.name}</option>
                                )
                            }
                        </Form.Control>
                    )
                }
            </AppContext.Consumer>
        )
    }
}

export default HtmlSelect;