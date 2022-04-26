import React, {Component} from 'react';
import HtmlSelect from "../HtmlSelect";
import axios from 'axios';
import AppContext from "../../app-params";
import {Button, Col, Form, Row} from "react-bootstrap";

class IngredientSimpleForm extends Component {
    static contextType = AppContext;


    defaultValues = {
        id: '',
        name: '',
        units: '',
        ingredientType: '',
        minimum: 100
    };

    componentDidMount() {
        this.nameInput.focus();
    }

    constructor(props) {
        super(props);
        this.state = {
            values: (
                props.ingredient ? {
                    id: props.ingredient.id ?? this.defaultValues.id,
                    name: props.ingredient.name ?? this.defaultValues.name,
                    units: (props.ingredient.units && props.ingredient.units.id) ? props.ingredient.units.id : this.defaultValues.units,
                    ingredientType: (props.ingredient.type && props.ingredient.type.id) ? props.ingredient.type.id : this.defaultValues.ingredientType,
                    minimum: props.ingredient.minimum ?? this.defaultValues.minimum
                } : this.defaultValues
            ),
            isSubmitting: false,
            isError: false
        };
    }

    handleInputChange = (e) => {
        this.setState({
            values: {...this.state.values, [e.target.name]: e.target.value}
        });
    }

    checkEnter = (e) => {
        if (e.key === 'Enter') {
            this.submitForm(e);
        }
    }

    submitForm = (e) => {
        e.preventDefault();

        axios.post('/app/ingredients/add', this.state.values).then(obResponse => {
            if (obResponse.data.status) {
                this.props.onSave(obResponse.data.item);
                this.setState({values: this.defaultValues});
                this.context.displaySuccess(obResponse.data.message);
            } else {
                this.context.displayError(obResponse.data.message);
            }
        })

    }

    render() {
        return (
            <>
                <Row className={'ingredient-form'}>
                    <Form.Control type="hidden" name="id" value={this.state.values.id}/>
                    <Col lg={5} xs={12}>
                        <Form.Control
                            type="text"
                            ref={(input) => {
                                this.nameInput = input
                            }}
                            onKeyPress={this.checkEnter}
                            value={this.state.values.name}
                            name='name'
                            onChange={this.handleInputChange}
                            placeholder="Название"
                            required
                        />

                        <Form.Text className="text-muted">
                            Введите название ингредиента
                        </Form.Text>
                    </Col>
                    <Col lg={2} xs={6}>
                        <HtmlSelect
                            name='ingredientType'
                            current_value={this.state.values.ingredientType}
                            onChange={this.handleInputChange}/>
                        <Form.Text className="text-muted">
                            Выберите тип ингредиента
                        </Form.Text>
                    </Col>
                    <Col lg={2} xs={6}>
                        <HtmlSelect
                            name='units'
                            current_value={this.state.values.unit}
                            onChange={this.handleInputChange}/>
                        <Form.Text className="text-muted">
                            Выберите единицу измерения
                        </Form.Text>
                    </Col>
                    <Col lg={2} xs={12}>
                        <input type="number"
                               name={'minimum'}
                               className="form-control"
                               onChange={this.handleInputChange}
                               onKeyPress={this.checkEnter}
                               value={this.state.values.minimum}
                               placeholder="Минимум для закупки"/>
                        <Form.Text className="text-muted">
                            Укажите минимальный размер закупки
                        </Form.Text>
                    </Col>
                    <Col lg={1} xs={12}>
                        <Button
                            onClick={this.submitForm}
                            variant={'success'}
                            className="btn-sm"
                        >Сохранить</Button>
                        {
                            this.props.onClose &&
                            <Button
                                onClick={() => this.props.onClose(false)}
                                variant={'link'}
                                color={'danger'}
                                className="btn-sm"
                            >Отменить</Button>
                        }
                    </Col>
                </Row>
            </>
        )
    }
}

export default IngredientSimpleForm;