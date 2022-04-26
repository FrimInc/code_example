import {Card, Col, ListGroup, Row} from "react-bootstrap";
import React from "react";
import TypeShow from "../TypeShow";
import OneIngredient from "../ingredients/OneIngredient";
import PubStatusShow from "../PubStatusShow";
import DifficultView from "../DifficultView";


class RecipeBlock extends React.Component {
    constructor(props) {
        super(props);
    }

    render() {
        const recipe = this.props.recipe;
        return (
            <>
                {
                    !this.props.noname &&
                    <Row>
                        <Col xs={12}>
                            {
                                recipe.isMine &&
                                <PubStatusShow item={recipe}/>
                            }
                            <h1>
                                {recipe.name}
                            </h1>
                        </Col>
                    </Row>
                }
                <Row>
                    <Col xs={12} lg={6}>
                        <ListGroup>
                            <ListGroup.Item>
                                <TypeShow type={recipe.type}/><br/>
                                <i className={'tags'}>
                                    {
                                        recipe.tags.map((tag) => {
                                                return tag.tag.name;
                                            }
                                        )
                                    }
                                </i>
                            </ListGroup.Item>
                            <ListGroup.Item>Автор {recipe.author.fullName}</ListGroup.Item>
                            {
                                recipe.totalTime > 0 &&
                                <ListGroup.Item>Время готовки {recipe.totalTime} мин.</ListGroup.Item>
                            }
                            {
                                recipe.activeTime > 0 &&
                                <ListGroup.Item>Активное время {recipe.activeTime} мин.</ListGroup.Item>
                            }

                            <ListGroup.Item>Сложность: <DifficultView value={recipe.difficult}/></ListGroup.Item>
                            {
                                recipe.kkal > 0 &&
                                <ListGroup.Item>Калорийность ~ {recipe.kkal} ккал</ListGroup.Item>
                            }
                            <ListGroup.Item>Порций: {recipe.serving}</ListGroup.Item>
                        </ListGroup>
                    </Col>
                    <Col xs={12} lg={6} className="border-left border-right border-light">
                        {recipe.anounce}
                        {recipe.ingredients.map((ingredient, ingredientIndex) =>
                            <OneIngredient ingredient={ingredient} key={ingredientIndex}/>
                        )}
                    </Col>
                </Row>
                <h4>Рецепт</h4>
                {recipe.stages.map((stage, stageIndex) =>
                    <Card key={stageIndex} body>{stage}</Card>
                )}
            </>
        )
    }

}

export default RecipeBlock;