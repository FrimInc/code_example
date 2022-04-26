import {Button, Form, InputGroup} from "react-bootstrap";
import React, {Component} from 'react';

class AmountSelector extends Component {

    roundAs = (value) => {
        if (this.props.step > 1) {
            return Math.floor(value / this.props.step) * this.props.step;
        }
        return value;
    }

    render() {
        return (
            <div className={'amount-select ' + (this.props.className ? this.props.className : '')}>
                <div className={'select-left ' + (this.props.opened ? 'selector-visible' : '')}>
                    {
                        this.props.counts.map((count) =>
                            <Button
                                key={count}
                                size={'sm'}
                                variant={'link'}
                                onClick={() => this.props.onChange(this.roundAs(this.props.value * 1 + count))}
                            >
                                +{count}
                            </Button>
                        )
                    }
                </div>
                <div className={'select-right ' + (this.props.opened ? 'selector-visible' : '')}>
                    {
                        this.props.counts.reverse().map((count) =>
                            <Button
                                key={count}
                                variant={'link'}
                                size={'sm'}
                                onClick={() => this.props.onChange(this.roundAs(this.props.value - count))}
                            >
                                -{count}
                            </Button>
                        )
                    }
                </div>
                <InputGroup className={'input-select'}>
                    {
                        this.props.taste &&
                        <InputGroup.Prepend>
                            <InputGroup.Text className={'taste-prepend'}>
                                <Form.Check
                                    type={'checkbox'}
                                    checked={this.props.tasteValue}
                                    id={'taste_' + this.props.index}
                                    label={'По вкусу'}
                                    onChange={this.props.onChangeTaste}
                                />
                            </InputGroup.Text>
                        </InputGroup.Prepend>
                    }
                    <Form.Control
                        type={'number'}
                        readOnly={this.props.tasteValue}
                        required={this.props.required}
                        placeholder={this.props.placeholder}
                        value={this.props.value}
                        step={this.props.step}
                        min={this.props.min}
                        onChange={this.props.onChange}
                        name={this.props.name}
                        index={this.props.index}
                    />
                    {
                        this.props.append &&
                        <InputGroup.Append>
                            <InputGroup.Text className={'width-50px'}>
                                {this.props.append}
                            </InputGroup.Text>
                        </InputGroup.Append>
                    }
                    {
                        !this.props.append
                        &&
                        <InputGroup.Append>
                            <InputGroup.Text className={'width-50px'}><i className={'bi bi-cup'}/></InputGroup.Text>
                        </InputGroup.Append>
                    }
                </InputGroup>
            </div>
        )
    }
}

export default AmountSelector;