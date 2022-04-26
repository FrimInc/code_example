import React, {Component} from 'react';
import {Button, Form, Modal} from "react-bootstrap";
import AppContext from "../app-params";
import {GoogleReCaptcha} from "react-google-recaptcha-v3";

class Auth extends Component {

    static contextType = AppContext;

    constructor(props) {
        super(props);

        this.state = {
            action: 'auth',
            validated: false,
            values: {
                captchaVal: 0,
                login: '',
                password: '',
                confirmPassword: '',
                recaptchaToken: '',
                name: '',
                lastName: '',
                secret: ''
            }
        }
        this.form = React.createRef();
    }

    updateCaptcha = () => {

        this.setState({
            values: {
                ...this.state.values, captchaVal: Math.random()
            }
        });

    }

    componentDidMount() {
        this.checkValidation();
        setTimeout(() => this.updateCaptcha(), 100);
    }

    checkValidation = () => {
        let validity = this.form.current.checkValidity();

        if (validity === false) {
            this.setState({validated: true});
            this.context.displayError('Проверьте правильность заполнения полей');
        } else {
            this.setState({validated: false});
        }

        return validity;
    }

    makeAuth = (event) => {
        event.preventDefault();
        event.stopPropagation();

        this.context.obAxios.post('/auth', this.state.values).then(obResponse => {
            if (obResponse.status) {
                this.context.displaySuccess(obResponse.message);
                this.context.app.getAppData();
            } else {
                this.context.displayError(obResponse.message);
            }
            setTimeout(() => this.updateCaptcha(), 100);
        })
    }

    makeRegister = (event) => {
        event.preventDefault();
        event.stopPropagation();

        this.context.obAxios.post('/register', this.state.values).then(obResponse => {
            if (obResponse.status) {
                this.setState({action: 'auth'});
                this.context.displaySuccess(obResponse.message);
            } else {
                this.context.displayError(obResponse.message);
            }
            setTimeout(() => this.updateCaptcha(), 100);
        })
    }

    handleRecaptcha = (token) => {
        console.log('token', token);
        this.setState({
            values: {...this.state.values, recaptchaToken: token}
        }, () => this.checkValidation());
    }

    setAction = (action) => {
        this.setState({
            action: action
        });
    }

    handleInputChange = (e) => {
        this.setState({
            values: {...this.state.values, [e.target.name]: e.target.value}
        }, () => this.checkValidation());
    }

    render() {

        const values = this.state.values;
        const action = this.state.action;
        const validated = this.state.validated;

        return (
            <Modal show={true} onHide={() => {
            }}>
                <Form
                    onSubmit={action === 'auth' ? this.makeAuth : this.makeRegister}
                    noValidate
                    validated={validated}
                    ref={form => this.form.current = form}
                >
                    <Modal.Header>
                        <Modal.Title>Пожалуйста, авторизуйтесь</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        <Form.Group controlId="formBasicEmail">
                            <Form.Label>Email</Form.Label>
                            <Form.Control
                                type="email"
                                placeholder="Введите email"
                                name={'login'}
                                value={values.login}
                                onChange={this.handleInputChange}
                            />
                        </Form.Group>
                        <Form.Group controlId="formBasicPassword">
                            <Form.Label>Пароль</Form.Label>
                            <Form.Control
                                type="password"
                                placeholder="Пароль"
                                value={values.password}
                                name={'password'}
                                onChange={this.handleInputChange}
                            />
                        </Form.Group>
                        {
                            action === 'register' &&
                            <>
                                <Form.Group controlId="formBasicConfirmPassword">
                                    <Form.Label>Повторите пароль</Form.Label>
                                    <Form.Control
                                        type="password"
                                        placeholder="Повторите пароль"
                                        value={values.confirmPassword}
                                        name={'confirmPassword'}
                                        onChange={this.handleInputChange}
                                    />
                                </Form.Group>
                                <Form.Group controlId="formBasicName">
                                    <Form.Label>Имя</Form.Label>
                                    <Form.Control
                                        type="text"
                                        placeholder="Имя"
                                        value={values.name}
                                        name={'name'}
                                        onChange={this.handleInputChange}
                                    />
                                </Form.Group>
                                <Form.Group controlId="formBasicLastName">
                                    <Form.Label>Фамилия</Form.Label>
                                    <Form.Control
                                        type="text"
                                        placeholder="Фамилия"
                                        value={values.lastName}
                                        name={'lastName'}
                                        onChange={this.handleInputChange}
                                    />
                                </Form.Group>
                                <Form.Group controlId="formBasicSecret">
                                    <Form.Label>Секретик</Form.Label>
                                    <Form.Control
                                        type="text"
                                        placeholder="Секретик"
                                        value={values.secret}
                                        name={'secret'}
                                        onChange={this.handleInputChange}
                                    />
                                </Form.Group>
                            </>

                        }

                    </Modal.Body>
                    <Modal.Footer>
                        <GoogleReCaptcha
                            key={values.captchaVal}
                            onVerify={this.handleRecaptcha}
                        />
                        <Button
                            variant="link"
                            onClick={() => this.setAction(action === 'auth' ? 'register' : 'auth')}
                        >
                            {action === 'auth' ? 'Зарегистрироваться' : 'Авторизоваться'}
                        </Button>
                        <Button type={'submit'}
                                variant="primary">{action === 'auth' ? 'Авторизоваться' : 'Зарегистрироваться'}</Button>
                    </Modal.Footer>
                </Form>
            </Modal>
        )
    }
}

export default Auth;