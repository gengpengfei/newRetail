
'use strict';

import React, {
  Component,
} from 'react';

import {
  StyleSheet,
  Text,
  View,
  Dimensions,
  Animated
} from 'react-native';

import PropTypes from 'prop-types';
const {width , height} = Dimensions.get('window');

export default class MyModal extends Component {

    static propTypes = {
        topHeight: PropTypes.number,

    };

    static defaultProps = {
        topHeight:0,

    };

    constructor(props) {
        super(props);
        this.renderContent = this.renderContent.bind(this);
        this.open = this.open.bind(this);
        this.close = this.close.bind(this);


        this.state = {
            isOpen:false,
            fadeInOpacity : new Animated.Value(0),
            
        };
    }

    openAnimate=()=>{

        Animated.sequence([            // 首先执行decay动画，结束后同时执行spring和twirl动画
            this.createFade(1),
            
          ]).start(); 
    }

    closeAnimate=()=>{

        Animated.sequence([            // 首先执行decay动画，结束后同时执行spring和twirl动画
            this.createFade(0),
            
          ]).start(); 
    }

    createFade=(value)=>{
        return Animated.timing(                 
            this.state.fadeInOpacity,               
            {
                toValue: value,                 
                duration : 100, 
            }
        );
    }


    renderContent(){

        let enabled = this.state.isOpen;
        return(

            <Animated.View pointerEvents={enabled ? 'auto':'none'} style={{flex:1,opacity: this.state.fadeInOpacity,}}>
                {this.props.children}
            </Animated.View>
            
        )
    }


    render() {
        
            return (
            <View 
            {...this.props}
            style ={{position:'absolute', top:this.props.topHeight, width:width, height:height - this.props.topHeight}} pointerEvents={this.state.isOpen?"auto":"none"}
            >

                {this.state.isOpen && this.renderContent()}
                
            </View>
            );
    }


    /****************** PUBLIC METHODS **********************/
    open(){

        if(!this.state.isOpen){
            this.setState({
                isOpen:true,
            },()=>{
                this.openAnimate();
            })
        }

    }

    close(){

        if(this.state.isOpen){

            this.setState({
                isOpen:false,
            },()=>{
                this.closeAnimate();
            })
        }
    }

}

const styles = StyleSheet.create({
  
});