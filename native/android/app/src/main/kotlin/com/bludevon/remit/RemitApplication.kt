package com.bludevon.remit

import android.app.Application
import dev.hotwire.core.bridge.KotlinXJsonConverter
import dev.hotwire.core.config.Hotwire
import dev.hotwire.core.turbo.config.PathConfiguration
import dev.hotwire.navigation.config.defaultFragmentDestination
import dev.hotwire.navigation.config.registerFragmentDestinations
import dev.hotwire.navigation.fragments.HotwireWebBottomSheetFragment
import dev.hotwire.navigation.fragments.HotwireWebFragment

class RemitApplication : Application() {
    override fun onCreate() {
        super.onCreate()

        // Screens: a web fragment, and a bottom sheet for Archives (see the path configuration).
        Hotwire.defaultFragmentDestination = HotwireWebFragment::class
        Hotwire.registerFragmentDestinations(
            HotwireWebFragment::class,
            HotwireWebBottomSheetFragment::class,
        )

        Hotwire.config.jsonConverter = KotlinXJsonConverter()
        Hotwire.config.applicationUserAgentPrefix = "Remit;"
        Hotwire.config.webViewDebuggingEnabled = BuildConfig.DEBUG

        Hotwire.loadPathConfiguration(
            context = this,
            location = PathConfiguration.Location(
                assetFilePath = "json/path-configuration.json",
                remoteFileUrl = "${BuildConfig.BASE_URL}/config/android_v1.json",
            ),
        )
    }
}
