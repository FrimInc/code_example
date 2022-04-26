import {Badge, OverlayTrigger, Tooltip} from "react-bootstrap";
import React from "react";
import {AiOutlineEye, AiOutlineEyeInvisible, TiZoom} from "react-icons/all";

class PubStatusShow extends React.Component {
    render() {
        return (
            <OverlayTrigger
                overlay={<Tooltip
                    id="tooltip-disabled"
                    className={'print-hidden'}
                >
                    {{
                        O: 'Опубликован',
                        P: 'Приватный',
                        M: 'На модерации',
                    }[this.props.item.access]}
                </Tooltip>}
            >
                <div className={'pub-status-block d-none d-md-block mt-2'}>
                    <Badge
                        className={'inner-pub-block'}
                        size={'sm'}
                        variant={{
                            O: 'success',
                            P: 'secondary',
                            M: 'warning',
                        }[this.props.item.access]}
                    >
                        {this.props.item.access === 'O' && <AiOutlineEye/>}
                        {this.props.item.access === 'P' && <AiOutlineEyeInvisible/>}
                        {this.props.item.access === 'M' && <TiZoom/>}
                    </Badge>
                </div>
            </OverlayTrigger>
        )
    }
}

export default PubStatusShow;