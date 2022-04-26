import React, {Component} from 'react';
import AppContext from "../app-params";
import {Button, Col, ListGroup, Row} from 'react-bootstrap';
import Loader from "../utils/Loader";
import {Link} from "react-router-dom";

class ShopLists extends Component {
    static contextType = AppContext;

    constructor(props) {
        super(props);
        this.state = {shopList: [], loading: true};
    }

    componentDidMount() {
        this.getShopLists();
        this.context.setTitle('Все списки покупок');
    }

    getShopLists = () => {
        this.context.obAxios.get(`/app/shopLists`).then(obResponse => {
            this.setState({shopList: obResponse, loading: false})
        })
    }

    deleteShopList = (id) => {
        this.context.obAxios.post(`/app/shopLists/delete`, {id: id}).then(obResponse => {
            if (obResponse.status) {
                this.getShopLists();
                this.context.displaySuccess(obResponse.message);
            } else {
                this.context.displayError(obResponse.message);
            }
        })
    }

    setMain = (id) => {
        this.context.obAxios.post(`/app/shopLists/setMain`, {id: id}).then(obResponse => {
            if (obResponse.status) {
                this.getShopLists();
                this.context.displaySuccess(obResponse.message);
            } else {
                this.context.displayError(obResponse.message);
            }
        })
    }

    render() {
        const loading = this.state.loading;
        return (
            <Row>
                <Col lg={2}>
                    <ListGroup variant={'flush'}>
                        <ListGroup.Item>
                            <Link to='/shopList/0'>
                                <Button variant={"primary"}>Добавить список</Button>
                            </Link>
                        </ListGroup.Item>
                    </ListGroup>
                </Col>
                <Col xs={12} lg={10}>
                    {loading ? <Loader/> :
                        this.state.shopList.map((shopList, shopListIndex) =>
                            <Row className={'border border-primary'}
                                 key={shopListIndex}>
                                {
                                    shopList.isMineReal &&
                                    <Col xs={3}>
                                        <Button
                                            className={'mt-1'}
                                            size={'sm'}
                                            variant={
                                                shopList.main
                                                    ? 'success'
                                                    : 'link'
                                            }
                                            as={
                                                shopList.main
                                                    ? 'span'
                                                    : 'button'
                                            }
                                            onClick={
                                                (e) =>
                                                    !shopList.main && this.setMain(shopList.id, e)
                                            }
                                        >
                                            <i className={
                                                'bi ' +
                                                (
                                                    shopList.main
                                                        ? 'bi-pencil-fill'
                                                        : 'bi-pencil'
                                                )
                                            }>&nbsp;</i> {
                                            shopList.main
                                                ? 'Основной'
                                                : 'Назначить основным'
                                        }
                                        </Button>
                                    </Col>
                                }
                                <Col xs={7} lg={7}>
                                    <Link to={shopList.viewLink} className='nav-link'>
                                        {shopList.name}
                                    </Link>
                                </Col>
                                <Col className={'d-none d-lg-block'} xs={2}>
                                    {shopList.canEdit && (
                                        <Button
                                            variant="danger"
                                            onClick={(e) => this.deleteShopList(shopList.id, e)}
                                        >
                                            <i className={'bi bi-trash'}>&nbsp;</i>
                                        </Button>
                                    )}
                                </Col>
                            </Row>
                        )}
                </Col>

            </Row>
        )
    }
}

export default ShopLists;