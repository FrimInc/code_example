import React, {Component} from "react";

class TimeView extends Component {
    render() {
        return (
            <>
                {
                    this.props.recipe.activeTime > 0 &&
                    this.props.recipe.totalTime &&
                    <i className={'bi bi-clock'}>&nbsp;{this.props.recipe.activeTime} / {this.props.recipe.totalTime} мин.</i>
                }
                {
                    this.props.recipe.activeTime > 0 &&
                    !this.props.recipe.totalTime &&
                    <i className={'bi bi-clock'}>&nbsp;{this.props.recipe.activeTime} мин.</i>
                }
                {
                    this.props.recipe.totalTime > 0 &&
                    !this.props.recipe.activeTime &&
                    <i className={'bi bi-clock'}>&nbsp;{this.props.recipe.totalTime} мин.</i>
                }
            </>
        );
    }

}

export default TimeView;