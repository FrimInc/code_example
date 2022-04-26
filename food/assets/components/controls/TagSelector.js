import axios from 'axios';
import React from 'react';
import TextField from '@material-ui/core/TextField';
import Autocomplete from '@material-ui/lab/Autocomplete';
import CircularProgress from '@material-ui/core/CircularProgress';

let searchTimeout = null;

export default function TagSelector(props) {
    const [value, setValue] = React.useState(null);
    const [open, setOpen] = React.useState(false);
    const [search, setSearch] = React.useState(null);
    const [options, setOptions] = React.useState([]);
    const loading = open && options.length === 0;

    React.useEffect(() => {
        if (value) {
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
                axios.get(`/app/autocomplete/tags?search=` + search).then(obResponse => {
                    if (active) {
                        setOptions(Object.keys(obResponse.data).map((key) =>
                            obResponse.data[key]
                        ));
                    }
                });
            }, 1000);
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
                    setValue(newValue);
                }}
                onInputChange={(event, newInputValue) => {
                    setSearch(newInputValue);
                }}
                getOptionSelected={(option, value) => option.name === value.name}
                getOptionLabel={(option) => option.name}
                options={options}
                loading={loading}
                loadingText={'Поиск...'}
                renderInput={(params) => (
                    <TextField
                        {...params}
                        label="Ключевое слово"
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