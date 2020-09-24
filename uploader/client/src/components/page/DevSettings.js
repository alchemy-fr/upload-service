import React, {Component} from 'react';
import config from '../../config';
import {Form, Button} from "react-bootstrap";
import Container from "../Container";

export default class DevSettings extends Component {
    constructor(props) {
        super(props);

        const {clientId, clientSecret} = config.getClientCredential();

        this.state = {
            uploadBaseUrl: config.getUploadBaseURL() || '',
            authBaseUrl: config.getAuthBaseUrl() || '',
            directLoginForm: !!config.isDirectLoginForm(),
            clientId: clientId || '',
            clientSecret: clientSecret || '',
            saved: false,
        }
    }

    handleChange = event => {
        const value = event.target.type === 'checkbox'
        ? !!event.target.checked
            : event.target.value;

        this.setState({
            [event.target.id]: value,
            saved: false,
        });
    };

    handleSubmit = event => {
        event.preventDefault();

        config.setUploadBaseURL(this.state.uploadBaseUrl);
        config.setAuthBaseURL(this.state.authBaseUrl);
        config.setClientCredential({
            clientId: this.state.clientId,
            clientSecret: this.state.clientSecret,
        });
        config.setDirectLoginForm(this.state.directLoginForm);

        this.setState({
            saved: true,
        }, () => {
            setTimeout(() => {
                this.setState({saved: false});
            }, 3000);
        });
    };

    render() {
        const {saved} = this.state;

        return (
            <Container title="DEV Settings">
                <Form onSubmit={this.handleSubmit}>
                    <Form.Group controlId="uploadBaseUrl">
                        <Form.Label>Upload Base URL</Form.Label>
                        <Form.Control
                            type="text"
                            value={this.state.uploadBaseUrl}
                            onChange={this.handleChange}
                        />
                    </Form.Group>
                    <Form.Group controlId="authBaseUrl">
                        <Form.Label>Auth Base URL</Form.Label>
                        <Form.Control
                            type="text"
                            value={this.state.authBaseUrl}
                            onChange={this.handleChange}
                        />
                    </Form.Group>
                    <Form.Group controlId="clientId">
                        <Form.Label>Client ID</Form.Label>
                        <Form.Control
                            value={this.state.clientId}
                            onChange={this.handleChange}
                            type="text"
                        />
                    </Form.Group>
                    <Form.Group controlId="clientSecret">
                        <Form.Label>Client secret</Form.Label>
                        <Form.Control
                            value={this.state.clientSecret}
                            onChange={this.handleChange}
                            type="text"
                        />
                    </Form.Group>
                    <Form.Group controlId="directLoginForm">
                        <Form.Label>Direct login form?</Form.Label>
                        <Form.Control
                            checked={this.state.directLoginForm}
                            onChange={this.handleChange}
                            type="checkbox"
                        />
                    </Form.Group>
                    <Button
                        block
                        type="submit"
                    >
                        Save
                    </Button>
                    {saved ? (<span>
                        {' '}
                            <span className="badge badge-success">saved!</span>
                        </span>
                    ) : ''}
                </Form>
            </Container>
        );
    }
}
