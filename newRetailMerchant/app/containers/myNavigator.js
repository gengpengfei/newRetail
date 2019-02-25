/**
 * https://www.cnblogs.com/CrazyWL/p/7283600.html 具体详细使用
 * 
 */
import { StackNavigator, TabNavigator, TabBarBottom } from 'react-navigation';
import { connect } from 'react-redux';
import { tabBarIconStyle } from "../utils/appTheme";
import CardStackStyleInterpolator from '../components/RNTransition';
import Home from './Home/Home_index'
import Message from './Message/Message_index'
import Mine from './Mine/Mine_index'
import ShopInfo from './Mine/ShopInfo';
import ShopManger from './Mine/ShopManager';
import MineWorkTime from './Mine/MineWorkTime';
import EnvironmentMachine from './Mine/EnvironmentMachine';
import FinalcialYue from './Mine/FinalcialYue';
import FinalcialWithdraw from './Mine/FinalcialWithdraw';
import ReadyPay from './Mine/ReadyPay';
import OutOfTime from './Mine/outOfTime';
import CheckPayDetail from './Mine/CheckPayDetail';
import ShopIntelligence from './Mine/ShopIntelligence';
import HomeShopIntelligence from './Home/HomeShopIntelligence';//提交资质
import CreateShop from './Home/CreateShop';//创建门店
import ConfirmIntelligence from './Home/confirmIntelligence';//认证资质门店认领完成
import CreateShopAddress from './Home/CreateShopAddress';//创建新门店门店地址
import ChooseType from './Home/ChooseType';//选择品类
import ConfirmShop from './Home/ConfirmShop';
import MineContract from './Mine/MineContract'
import MineSet from './Mine/MineSet'
import SystemMessage from './Message/systemMessage'
import Gathering from './Message/gathering';
import WithdrawCrash from './Message/withdrawCrash'
import BillFinish from './Message/billFinish'
import Activity from './Message/activity';
import Coupon from './Home/coupon';
import CouponDetail from './Home/couponDetail';
import PayPassword from './Mine/PayPassword'
import MineIntegrity from './Mine/MineIntegrity'
import MineNotifications from './Mine/MineNotifications'
import MineHelp from './Mine/MineHelp'
import MineHelpOnline from './Mine/MineHelpOnline'
import MineHelpDetail from './Mine/MineHelpDetail'
import MineAbout from './Mine/MineAbout'
import VerCode from './Home/VerCode'
import Scan from './Home/Scan'
import Order from './Home/order'
import MineOpinion from './Mine/MineOpinion'
import loginAccount from './LoginPage/loginAccount'
import Regist from './LoginPage/Regist'
import ForgetPassword from './LoginPage/ForgetPassword'
import FastLogin from './LoginPage/FastLogin'
import PaymentOrderDetail from './Home/paymentOrderDetail'
import ChangePassword from './LoginPage/ChangePassword'
import ModifiyMobile from './LoginPage/ModifiyMobile'
import ShopOrderDetail from './Home/shopOrderDetail'
import TimeSelection from './Home/TimeSelection'
import NewMobile from './LoginPage/NewMobile'
import MineSalesman from './Mine/MineSalesman'
import MineSalesmanCreate from './Mine/MineSalesmanCreate'
import MineSalesmanRemove from './Mine/MineSalesManRemove'
import cityPage from './Home/cityPage';
import MineOpinionNew from './Mine/MineOpinionNew';
import MineQRCode from './Mine/MineQRCode';
import Welcome from './Welcome/Welcome'
import ActivityDetail from './Message/activityDetail'

/***
 * 跳转动画，别删！！
 * 五种跳转动画，forHorizontal forVertical forFadeFromBottomAndroid forInitial mySearchTransition
 * 用法：navigate('Search',{ transition: 'mySearchTransition'})
 * ***/
const TransitionConfiguration = () => ({
    // transitionSpec: {
    //     duration:500,
    //     easing: Easing.bezier(0.2833, 0.99, 0.31833, 0.99),
    //     timing: Animated.timing,
    // },
    screenInterpolator: (sceneProps) => {
        const { scene } = sceneProps;
        const { route } = scene;
        const params = route.params || {};
        const transition = params.transition || 'forHorizontal';
        return CardStackStyleInterpolator[transition](sceneProps);
    },
});



const RootTabNav = TabNavigator(
    {
        Home: { screen: Home },
        Message: { screen: Message },
        Mine: { screen: Mine },

    },
    {
        initialRouteName: 'Home',
        swipeEnabled: false,
        animationEnabled: false,
        lazyLoad: true,
        navigationOptions: {
            headerLeft: null,
            gesturesEnabled: false,
        },
        tabBarComponent: TabBarBottom,
        tabBarPosition: 'bottom',
        tabBarOptions: {
            activeTintColor: tabBarIconStyle.activeColor,
            inactiveTintColor: tabBarIconStyle.inactiveColor,
            labelStyle: {
                fontSize: tabBarIconStyle.labelFontSize,
            },
        },
    }
);


//第一个参数 导航页面的配置 以及一些静态配置参数  第二个参数初始页面配置
const SimpleAppReactNavigation = StackNavigator(
    {
        Welcome: { screen: Welcome },
        loginAccount: { screen: loginAccount },
        RootTabNav: { screen: RootTabNav },
        ShopManger: { screen: ShopManger },
        MineWorkTime: { screen: MineWorkTime },
        EnvironmentMachine: { screen: EnvironmentMachine },
        FinalcialYue: { screen: FinalcialYue },
        FinalcialWithdraw: { screen: FinalcialWithdraw },
        ReadyPay: { screen: ReadyPay },
        OutOfTime: { screen: OutOfTime },
        ShopInfo: { screen: ShopInfo },
        CheckPayDetail: { screen: CheckPayDetail },
        ShopIntelligence: { screen: ShopIntelligence },
        HomeShopIntelligence: { screen: HomeShopIntelligence },
        CreateShop: { screen: CreateShop },
        ConfirmIntelligence: { screen: ConfirmIntelligence },
        CreateShopAddress: { screen: CreateShopAddress },
        ChooseType: { screen: ChooseType },
        ConfirmShop: { screen: ConfirmShop },

        MineContract: { screen: MineContract },
        MineSet: { screen: MineSet },
        SystemMessage: { screen: SystemMessage },
        Gathering: { screen: Gathering },
        WithdrawCrash: { screen: WithdrawCrash },
        BillFinish: { screen: BillFinish },
        Activity: { screen: Activity },
        ActivityDetail: { screen: ActivityDetail },
        Coupon: { screen: Coupon },
        CouponDetail: { screen: CouponDetail },
        PayPassword: { screen: PayPassword },
        MineIntegrity: { screen: MineIntegrity },
        MineNotifications: { screen: MineNotifications },
        MineHelp: { screen: MineHelp },
        MineHelpOnline: { screen: MineHelpOnline },
        MineHelpDetail: { screen: MineHelpDetail },
        MineAbout: { screen: MineAbout },
        VerCode: { screen: VerCode },
        Scan: { screen: Scan },
        MineOpinion: { screen: MineOpinion },
        Regist: { screen: Regist },
        ForgetPassword: { screen: ForgetPassword },
        FastLogin: { screen: FastLogin },
        Order: { screen: Order },
        ChangePassword: { screen: ChangePassword },
        ShopOrderDetail: { screen: ShopOrderDetail },
        ModifiyMobile: { screen: ModifiyMobile },
        PaymentOrderDetail: { screen: PaymentOrderDetail },
        TimeSelection: { screen: TimeSelection },
        NewMobile: { screen: NewMobile },
        PaymentOrderDetail: { screen: PaymentOrderDetail },
        MineSalesman: { screen: MineSalesman },
        MineSalesmanCreate: { screen: MineSalesmanCreate },
        cityPage: { screen: cityPage },
        MineOpinionNew: { screen: MineOpinionNew },
        MineSalesmanRemove: { screen: MineSalesmanRemove },
        MineQRCode: { screen: MineQRCode },
    },
    {
        initialRouteName: 'Welcome',
        transitionConfig: TransitionConfiguration,
        navigationOptions: {
            gesturesEnabled: false,
        }
    }
);

export default connect()(SimpleAppReactNavigation);