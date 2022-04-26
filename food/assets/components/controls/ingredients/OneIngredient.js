import {Badge} from "react-bootstrap";
import React from "react";


class OneIngredient extends React.Component {
    constructor(props) {
        super(props);
    }

    render() {
        return (
            <div
                className={
                    this.props.className
                        ? this.props.className
                        : "my-1 mr-1 pl-1 border-left border-secondary"
                }
            >
                {this.props.ingredient.ingredient.name}
                <Badge variant="light" className="ml-1">
                    <i>
                        {
                            this.props.ingredient.taste
                                ? 'По вкусу'
                                : this.props.ingredient.amount + ' ' + this.props.ingredient.ingredient.units.short
                        }
                    </i>
                </Badge>
            </div>
        )
    }
}

export default OneIngredient;