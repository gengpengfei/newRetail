import React, {Component} from 'react';
import {Platform, TextInput} from 'react-native';

 class MyTextInput extends Component {
    shouldComponentUpdate(nextProps){
        return Platform.OS !== 'ios' || this.props.value === nextProps.value;
    }
    render() {
        return <TextInput {...this.props} />;
    }
};

module.exports = MyTextInput;
