import React, {Component} from 'react';
import AppContext from "../app-params";
import {Button, Col, ListGroup, Row} from 'react-bootstrap';
import Loader from "../utils/Loader";
import {Link} from "react-router-dom";
import PubStatusShow from "../controls/PubStatusShow";

class Menus extends Component {
    static contextType = AppContext;

    constructor(props) {
        super(props);
        this.state = {menus: [], loading: true};
    }

    componentDidMount() {
        this.getMenus();
        this.context.setTitle('Все рецепты');
    }

    getMenus = () => {
        this.context.obAxios.get(`/app/menus`).then(obResponse => {
            this.setState({menus: obResponse, loading: false})
        })
    }

    deleteMenu = (id) => {
        this.context.obAxios.post(`/app/menus/delete`, {id: id}).then(obResponse => {
            if (obResponse.status) {
                this.getMenus();
                this.context.displaySuccess(obResponse.message);
            } else {
                this.context.displayError(obResponse.message);
            }
        })
    }

    setCurrent = (id) => {
        this.context.obAxios.post(`/app/menus/setCurrent`, {id: id}).then(obResponse => {
            if (obResponse.status) {
                if (typeof this.props.handleChange === 'function') {
                    this.props.handleChange();
                } else {
                    this.getMenus();
                }
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
                <Col lg={2} className={'d-none d-lg-block'}>
                    <ListGroup variant={'flush'}>
                        <ListGroup.Item>
                            <Link to='/menu/0/edit/'>
                                <Button variant={"primary"}>Добавить меню</Button>
                            </Link>
                        </ListGroup.Item>
                    </ListGroup>
                </Col>
                <Col xs={12} lg={10}>
                    {loading ? <Loader/> :
                        this.state.menus.map((menu, menuIndex) =>
                            <Row className={'border border-primary'}
                                 key={menuIndex}>
                                <Col xs={2} lg={2}>
                                    {
                                        menu.isMineReal &&
                                        <Button
                                            variant={
                                                menu.isCurrent
                                                    ? 'success'
                                                    : 'link'
                                            }
                                            as={
                                                menu.isCurrent
                                                    ? 'span'
                                                    : 'button'
                                            }
                                            onClick={
                                                (e) =>
                                                    !menu.isCurrent && this.setCurrent(menu.id, e)
                                            }
                                        >
                                            <i className={
                                                'bi ' +
                                                (
                                                    menu.isCurrent
                                                        ? 'bi-calendar-check'
                                                        : 'bi-calendar'
                                                )
                                            }>&nbsp;</i> {
                                            menu.isCurrent
                                                ? 'Основное'
                                                : 'Выбрать'
                                        }
                                        </Button>
                                    }
                                </Col>
                                <Col xs={10} lg={8}>
                                    <Link to={menu.viewLink} className='nav-link'>
                                        {menu.name}
                                        {
                                            menu.isMine &&
                                            <PubStatusShow item={menu}/>
                                        }
                                    </Link>
                                </Col>
                                <Col xs={12} lg={2} className={'d-none d-lg-block'}>
                                    {
                                        menu.canDelete &&
                                        <Button
                                            variant="danger"
                                            onClick={(e) => this.deleteMenu(menu.id, e)}
                                        >
                                            <i className={'bi bi-trash'}>&nbsp;</i>
                                        </Button>
                                    }
                                </Col>
                            </Row>
                        )}
                </Col>
            </Row>
        )
    }
}

export default Menus;