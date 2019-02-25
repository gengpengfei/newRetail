import React, {Component} from 'react';
import {
  StyleSheet,
  Text,
  View,
  Image,
  Dimensions,
  TouchableOpacity,
  ScrollView,
  TextInput,
} from 'react-native';
import {connect} from 'react-redux';
import Header from '../../components/Header/Header';
import Calendar from '../../components/CalendarSelect/Calendar';
// import Header from '../../components/Header/Header';

// import {Calendar, CalendarList, Agenda} from 'react-native-calendars';
class TimeSelection extends Component {
  static navigationOptions = ({navigation}) => ({
    header: null,
  });
  constructor (props) {
    super (props);
    this.month = new Date ().getMonth () + 1 >= 10
      ? new Date ().getMonth () + 1
      : '0' + (new Date ().getMonth () + 1);
    this.day = new Date ().getDate () >= 10
      ? new Date ().getDate ()
      : '0' + new Date ().getDate ();
    this.state = {
      startDate: null,
      endDate: null,
      SpecificDate: new Date ().getFullYear () + '' + this.month + this.day,
    };
    this.confirmDate = this.confirmDate.bind (this);
  }
  confirmDate({startDate, endDate, startMoment, endMoment}) {
    this.props.navigation.state.params.upTime (startDate, endDate);
    this.setState ({
      startDate,
      endDate,
    });
  }
  componentDidMount () {
    this.calendar && this.calendar.open ();
  }

  static navigationOptions = ({navigation}) => ({
    header: (
      <Header
        centerTitle="请选择日期"
        leftPress={() => {
          navigation.goBack ();
        }}
        rightText="确认"
      />
    ),
  });

  confirmDate({startDate, endDate, startMoment, endMoment}) {
    this.props.navigation.state.params.upTime (startDate, endDate);
    this.setState ({
      startDate,
      endDate,
    });
  }

  render () {
    let customI18n = {
      w: ['', '一', '二', '三', '四', '五', '六', '日'],
      weekday: ['', '一', '二', '三', '四', '五', '六', '日'],
      text: {
        start: '起始时间',
        end: '结束时间',
        date: '未选择',
        save: '保存',
        clear: '重置',
      },
      date: 'DD / MM', // date format
    };
    // optional property, too.
    let color = {
      subColor: '#f63300',
      mainColor: '#fff',
    };
    return (
      <View style={styles.container}>

        {/* <CalendarList
          onVisibleMonthsChange={months => {
            // console.log ('now these months are visible', months);
          }}
          pastScrollRange={50}
          futureScrollRange={50}
          scrollEnabled={true}
          showScrollIndicator={true}
          i18n="zh"
          onDayPress={day => {
            this.setState ({
              startDay: day,
            });
          }}
          markedDates={{
            this.state.startDay: {
              periods: [
                {startingDay: true, endingDay: false, color: '#f63300'},
              ],
            },
            '2017-08-20': {
              periods: [
                {startingDay: false, endingDay: true, color: '#f63300'},
              ],
            },
          }}
          markingType="multi-period"

          theme={'zh'}
        /> */}

        <Calendar
          Onavigation={this.props.navigation}
          i18n="zh"
          ref={calendar => {
            this.calendar = calendar;
          }}
          customI18n={customI18n}
          color={color}
          format="YYYYMMDD"
          minDate="20180101"
          maxDate={this.state.SpecificDate}
          startDate={this.state.startDate}
          endDate={this.state.endDate}
          onConfirm={this.confirmDate}
          close={() => {
            this.props.navigation.goBack ();
          }}
        />
      </View>
    );
  }
}

const styles = StyleSheet.create ({
  container: {
    flex: 1,
    backgroundColor: '#fff',
  },
});

function select (store) {
  return {
    username: store.userInfo.user_name,
  };
}
export default connect (select) (TimeSelection);
