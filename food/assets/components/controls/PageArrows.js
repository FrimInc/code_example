import React, {Component} from "react";
import {FaLongArrowAltLeft, FaLongArrowAltRight} from "react-icons/all";
import {Button, Col, Row} from "react-bootstrap";

class PageArrows extends Component {
    constructor(props) {
        super(props);
    }

    handlePageClick = () => {
        window.scrollTo({top: 0, behavior: 'smooth'});
        this.props.onPageChange(this.props.page + 1)
    }

    render() {
        return (
            <Row>
                <Button
                    as={'div'}
                    className={'col-sm-5'}
                    variant={'secondary'}
                    onClick={() => this.props.onPageChange(this.props.page - 1)}
                >
                    <FaLongArrowAltLeft/>
                </Button>
                <Col sm={2} className={'text-center'}>
                    <span className={'align-text-bottom'}>
                        Страница {this.props.page}
                    </span>
                </Col>
                <Button
                    as={'div'}
                    className={'col-sm-5'}
                    variant={'secondary'}
                    onClick={this.handlePageClick}
                >
                    <FaLongArrowAltRight/>
                </Button>
            </Row>
        )
    }
}

export default PageArrows;