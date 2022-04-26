import React, {Component} from "react";
import {Nav} from 'react-bootstrap';
import {Link} from "react-router-dom";
import AppContext from "../app-params";
import UserLine from "../utils/UserLine";

class TopMenu extends Component {
    static contextType = AppContext;

    constructor(props) {
        super(props);
    }

    render() {
        return (
            <>
                <Nav className="mr-auto">
                    {this.props.menu.map(menu_item =>
                        <Nav.Item
                            key={menu_item.title}
                            onClick={this.props.onNav}
                        >
                            <Link
                                className={'nav-link'}
                                to={menu_item.link}
                            >
                                {menu_item.title}
                            </Link>
                        </Nav.Item>
                    )}
                </Nav>
                <UserLine user={this.props.user} />
            </>
        )
    }
}

export default TopMenu;