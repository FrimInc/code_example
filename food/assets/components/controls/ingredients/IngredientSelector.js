import axios from 'axios';
import React from 'react';
import TextField from '@material-ui/core/TextField';
import Autocomplete, {createFilterOptions} from '@material-ui/lab/Autocomplete';
import CircularProgress from '@material-ui/core/CircularProgress';
import IngredientSimpleForm from "./IngredientSimpleForm";

const filter = createFilterOptions();
let searchTimeout = null;

export default function IngredientSelector(props) {
    const [value, setValue] = React.useState(null);
    const [newAddValue, setNewAddValue] = React.useState(null);
    const [open, setOpen] = React.useState(false);
    const [search, setSearch] = React.useState(null);
    const [openModal, setOpenModal] = React.useState(false);
    const [options, setOptions] = React.useState([]);
    const loading = open && options.length === 0;

    React.useEffect(() => {
        if (value) {
            setOpenModal(false);
            props.onSave(value);
            setValue(false);
        }
    });

    React.useEffect(() => {
        let active = true;

        if (!loading && !search) {
            return undefined;
        }

        clearTimeout(searchTimeout);

        if (search) {
            searchTimeout = setTimeout(() => {
                axios.get(`/app/autocomplete/ingredient?search=` + search).then(obResponse => {
                    if (active) {
                        setOptions(Object.keys(obResponse.data).map((key) =>
                            obResponse.data[key]
                        ));
                    }
                });
            }, 200);
        }

        return () => {
            active = false;
        };
    }, [loading, search]);

    React.useEffect(() => {
        if (!open) {
            setOptions([]);
        }
    }, [open]);

    return (
        <>
            {(openModal &&
                <IngredientSimpleForm onSave={setValue} onClose={setOpenModal} ingredient={{name: newAddValue}}/>
            )}
            <Autocomplete
                clearOnBlur
                value={null}
                blurOnSelect
                onOpen={() => {
                    setOpen(true);
                }}
                onClose={() => {
                    setOpen(false);
                }}
                onChange={(event, newValue) => {
                    if (typeof newValue === 'string') {
                        setTimeout(() => {
                            setOpenModal(true);
                        });
                    } else if (newValue && typeof newValue.inputValue != 'undefined') {
                        setNewAddValue(newValue.inputValue);
                        setOpenModal(true);
                    } else {
                        setValue(newValue);
                    }
                }}
                onInputChange={(event, newInputValue) => {
                    setSearch(newInputValue);
                }}
                filterOptions={(options, params) => {
                    const filtered = filter(options, params);

                    if (
                        params.inputValue !== ''
                        && (
                            filtered.filter(item => {
                                return params.inputValue.toLowerCase() === item.name.toLowerCase();
                            }).length === 0
                            ||
                            filtered.length === 0
                        )
                    ) {
                        filtered.push({
                            inputValue: params.inputValue,
                            name: `Создать "${params.inputValue}"`,
                        });
                    }

                    return filtered;
                }}
                getOptionSelected={(option, value) => option.name === value.name}
                getOptionLabel={(option) => option.name}
                options={options}
                loading={loading}
                loadingText={'Поиск...'}
                renderInput={(params) => (
                    <TextField
                        {...params}
                        label="Найти ингредиент"
                        variant="outlined"
                        InputProps={{
                            ...params.InputProps,
                            endAdornment: (
                                <React.Fragment>
                                    {loading ? <CircularProgress color="inherit" size={20}/> : null}
                                    {params.InputProps.endAdornment}
                                </React.Fragment>
                            ),
                        }}
                    />
                )}
            />


        </>
    );
}