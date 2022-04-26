import React, {Component} from 'react';
import {Route, Switch} from 'react-router-dom';
import MainPage from './pages/MainPage';
import Ingredients from './pages/Ingredients';
import Recipes from './pages/Recipes';
import TopMenu from './controls/TopMenu';
import axios from 'axios';
import AppContext from './app-params';
import {Helmet} from "react-helmet";
import {Container, Navbar} from "react-bootstrap";
import Loader from "./utils/Loader";
import RecipeView from "./pages/RecipeView";
import RecipeEdit from "./pages/RecipeEdit";
import Menus from "./pages/Menus";
import MenuEdit from "./pages/MenuEdit";
import Auth from "./pages/Auth";
import ShopLists from "./pages/ShopLists";
import ShopListView from "./pages/ShopListView";
import {GoogleReCaptchaProvider} from "react-google-recaptcha-v3";
import AppConstants from "./AppConstants";
import ProgressBar from "react-bootstrap/cjs/ProgressBar";

class Home extends Component {
    static contextType = AppContext;
    obAxios;
    progressTimeout;

    constructor(props) {
        super(props);
        this.state = {
            appData: {},
            error: '',
            navbarExpanded: false,
            title: 'Всё про еду',
            success: '',
            loading: true,
            loadingProgress: 0
        };
        this.initAxios();
    }

    makeProgressTimeout(value) {
        clearTimeout(this.progressTimeout);
        if (typeof value === 'undefined') {
            this.setState({
                loadingProgress: this.state.loadingProgress + (100 - this.state.loadingProgress) * 0.3
            }, () => {
                this.progressTimeout = setTimeout(() => {
                    this.makeProgressTimeout()
                }, 10)
            });
        } else if (value) {
            this.setState({
                loadingProgress: value
            }, () => {
                this.progressTimeout = setTimeout(() => {
                    this.makeProgressTimeout()
                }, 10)
            });
        } else {
            this.setState({
                loadingProgress: value
            });
        }
    }

    initAxios() {
        this.obAxios = axios.create();
        let _app = this;

        this.obAxios.interceptors.request.use(function (config) {
            _app.makeProgressTimeout(1);
            return config;
        }, function (error) {
            return Promise.reject(error);
        });

        this.obAxios.interceptors.response.use(function (obResponse) {
            _app.makeProgressTimeout(false);
            if (_app.checkNoError(obResponse.data)) {
                return obResponse.data;
            }
            return {};
        }, function (error) {
            _app.makeProgressTimeout(0);
            return Promise.reject(error);
        });
    }

    componentDidMount() {
        this.getAppData();
    }

    getAppData = () => {
        this.obAxios.get(`/main`).then(obResponse => {
            this.setStateTran({appData: obResponse, loading: false})
        })
    }

    checkNoError = response => {
        if (response.status === false) {
            this.displayError(response.message);
            return false;
        }
        return true;
    }

    setTitle = newTitle => {
        this.setState({title: newTitle});
    }

    clearError = () => {
        this.setState({success: '', error: ''});
    }

    displayError = errorText => {
        this.setState({error: errorText.message ? errorText.message : errorText, success: ''});
        setTimeout(this.clearError, 3000);
    }

    displaySuccess = successText => {
        this.setState({success: successText, error: ''});
        setTimeout(this.clearError, 3000);
    }

    setStateTran = data => {
        this.setState(data);
    };

    setNavBarExpanded = (value) => {
        this.setState({navbarExpanded: value})
    }

    render() {
        const loading = this.state.loading;

        const context = {
            appData: this.state.appData,
            app: this,
            obAxios: this.obAxios,
            displayError: this.displayError,
            displaySuccess: this.displaySuccess,
            setTitle: this.setTitle
        };

        const values = this.state;

        return (
            <AppContext.Provider value={context}>
                {loading ? (
                    <Loader/>
                ) : (
                    <>
                        {
                            values.appData.user &&
                            <>
                                <Container className={'main-container'}>
                                    <Navbar
                                        bg="dark"
                                        variant="dark"
                                        expand="lg"
                                        expanded={values.navbarExpanded}
                                        onToggle={(expanded) => this.setNavBarExpanded(expanded)}
                                    >
                                        <Navbar.Toggle aria-controls="responsive-navbar-nav"/>
                                        <Navbar.Collapse id="responsive-navbar-nav">
                                            <TopMenu
                                                onNav={() => this.setNavBarExpanded(false)}
                                                menu={values.appData.top_menu}
                                                user={values.appData.user}
                                            />
                                        </Navbar.Collapse>
                                    </Navbar>
                                    <div className={'progress-container'}>
                                        {
                                            values.loadingProgress > 0 &&
                                            <ProgressBar now={values.loadingProgress}/>
                                        }
                                    </div>
                                    <Switch>
                                        <Route exact path="/" component={MainPage}/>
                                        <Route exact path="/ingredients"
                                               render={(props) => <Ingredients
                                                   appProps={values.appData}
                                                   {...props}
                                               />}
                                        />
                                        <Route exact path="/recipes"
                                               render={(props) => <Recipes
                                                   appProps={values.appData}
                                                   {...props}
                                               />}/>
                                        <Route exact path="/recipe/:id"
                                               render={(props) => <RecipeView
                                                   id={props.match.params.id}
                                                   {...props}
                                               />}
                                        />
                                        <Route exact path="/recipe/:id/edit"
                                               render={(props) => <RecipeEdit
                                                   id={props.match.params.id}
                                                   {...props}
                                               />}
                                        />
                                        <Route exact path="/menus" component={Menus}/>
                                        <Route exact path="/menu/:id"
                                               render={(props) => <MenuEdit
                                                   editing={false}
                                                   id={props.match.params.id}
                                                   {...props}
                                               />}
                                        />
                                        <Route exact path="/menu/:id/edit"
                                               render={(props) => <MenuEdit
                                                   editing={true}
                                                   id={props.match.params.id}
                                                   {...props}
                                               />}
                                        />
                                        <Route exact path="/shopLists" component={ShopLists}/>
                                        <Route exact path="/shopList/:id"
                                               render={(props) => <ShopListView
                                                   id={props.match.params.id}
                                                   {...props}
                                               />}
                                        />
                                    </Switch>
                                    {
                                        values.loadingProgress > 0 &&
                                        <div className={'interface-locker'}/>
                                    }
                                </Container>
                            </>
                        }
                        {
                            !values.appData.user &&
                            <GoogleReCaptchaProvider
                                reCaptchaKey={AppConstants.recaptchaSiteKey}
                                language="ru"
                                useRecaptchaNet={false}
                                useEnterprise={false}
                            >
                                <Auth token={values.appData.token}/>
                            </GoogleReCaptchaProvider>
                        }
                        <Helmet>
                            <meta charSet="utf-8"/>
                            <title>{values.title}</title>
                        </Helmet>
                        {(values.error || values.success)
                        && (
                            <div
                                className={`alert error-handler alert-${values.error ? 'danger ' : ''}${values.success ? 'success' : ''}`}>
                                {values.error} {values.success}
                            </div>
                        )
                        }
                        <footer>
                        </footer>
                    </>
                )}
            </AppContext.Provider>
        )
    }

}

export default Home;