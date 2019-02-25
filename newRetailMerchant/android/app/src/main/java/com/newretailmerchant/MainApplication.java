package com.newretailmerchant;

import android.app.Application;

import com.facebook.react.ReactApplication;
import com.reactnativecomponent.barcode.RCTCapturePackage;
import com.BV.LinearGradient.LinearGradientPackage;
import com.reactnative.ivpusic.imagepicker.PickerPackage;
import com.rnfs.RNFSPackage;
import org.lovebing.reactnative.baidumap.BaiduMapPackage;
import cn.jpush.reactnativejpush.JPushPackage;
import cn.jpush.reactnativejpush.JPushPackage;
import com.reactnativecomponent.barcode.RCTCapturePackage;
import com.BV.LinearGradient.LinearGradientPackage;
import com.reactnative.ivpusic.imagepicker.PickerPackage;
import org.lovebing.reactnative.baidumap.BaiduMapPackage;
import com.facebook.react.ReactNativeHost;
import com.facebook.react.ReactPackage;
import com.facebook.react.shell.MainReactPackage;
import com.facebook.soloader.SoLoader;
import java.util.Arrays;
import java.util.List;
import com.rnfs.RNFSPackage;
public class MainApplication extends Application implements ReactApplication {

 private ReactNativeHost mReactNativeHost = new ReactNativeHost(this) {
    @Override
    public boolean getUseDeveloperSupport() {
      return BuildConfig.DEBUG;
    }

    @Override
    protected List<ReactPackage> getPackages() {
      return Arrays.<ReactPackage>asList(
             new MainReactPackage(),
            // new RCTCapturePackage(),
            // new LinearGradientPackage(),
            // new PickerPackage(),
            //new RNFSPackage(),
            //new BaiduMapPackage(),
          //  new JPushPackage(!BuildConfig.DEBUG, !BuildConfig.DEBUG),
            new JPushPackage(!BuildConfig.DEBUG, !BuildConfig.DEBUG),
            new RNFSPackage(),
            new RCTCapturePackage(),
            new LinearGradientPackage(),
            new PickerPackage(),
            new BaiduMapPackage(getApplicationContext())//这一句,
           
           
          
      );
    }

    @Override
    protected String getJSMainModuleName() {
      return "index";
    }
  };

  @Override
  public ReactNativeHost getReactNativeHost() {
    return mReactNativeHost;
  }

  @Override
  public void onCreate() {
    super.onCreate();
    SoLoader.init(this, /* native exopackage */ false);
  }
  
   public void setReactNativeHost(ReactNativeHost reactNativeHost) {
    mReactNativeHost = reactNativeHost;
  }
 
  // @Override
  //  public ReactNativeHost getReactNativeHost() {
  //    return mReactNativeHost;
  //  }
}
 