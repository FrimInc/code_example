import React, {Component} from 'react';
import AppContext from "../app-params";
import {Col, Row} from "react-bootstrap";
import MenuEdit from "./MenuEdit";
import ShopListView from "./ShopListView";
import Loader from "../utils/Loader";
import Menus from "./Menus";

class MainPage extends Component {
    static contextType = AppContext;

    constructor(props) {
        super(props);
        this.state = {
            values: {},
            loading: true
        };

    }

    componentDidMount = () => {
        this.getMainPage();
    }

    setMainPage = (data) => {
        this.setState({values: data, loading: false})
    }

    getMainPage = () => {
        this.context.obAxios.get(`/app/mainPage`).then(obResponse => {
            if (this.context.app.checkNoError(obResponse)) {
                this.setMainPage(obResponse);
            } else {
                this.setState({values: {'menu': false, 'shopLists': false}, loading: false})
            }
        })
    }

    render() {

        const mainPage = this.state.values;
        const loading = this.state.loading;

        return (
            loading
                ? <Loader/>
                : <Row>
                    <Col xs={12} lg={12}>
                        {
                            mainPage.menu &&
                            <MenuEdit
                                id={mainPage.menu}
                                day={mainPage.day}
                                history={this.props.history}
                            />
                            ||
                            <Menus
                                handleChange={() => {
                                    this.getMainPage()
                                }}
                            />
                        }
                    </Col>
                    <Col xs={12} lg={12}>
                        <h2>Списки покупок</h2>
                        {
                            mainPage.shopLists &&
                            mainPage.shopLists.length > 0 &&
                            mainPage.shopLists.map(shopListId =>
                                <ShopListView
                                    key={shopListId}
                                    handleChange={() => {
                                        this.getMainPage()
                                    }}
                                    id={shopListId}
                                    hideControls={true}
                                    filterChecked={false}
                                />
                            )
                            ||
                            <h5>Всё куплено!</h5>
                        }
                    </Col>
                </Row>
        )
    }
}

export default MainPage;