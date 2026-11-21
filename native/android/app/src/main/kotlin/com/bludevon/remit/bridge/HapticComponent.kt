package com.bludevon.remit.bridge

import android.os.Build
import android.view.HapticFeedbackConstants
import dev.hotwire.core.bridge.BridgeComponent
import dev.hotwire.core.bridge.BridgeDelegate
import dev.hotwire.core.bridge.Message
import dev.hotwire.navigation.destinations.HotwireDestination

/** <p data-controller="bridge--haptic" data-bridge-feedback="success"> → a short vibration. */
class HapticComponent(
    name: String,
    private val delegate: BridgeDelegate<HotwireDestination>,
) : BridgeComponent<HotwireDestination>(name, delegate) {

    override fun onReceive(message: Message) {
        if (message.event != "vibrate") return

        val feedback = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
            HapticFeedbackConstants.CONFIRM
        } else {
            HapticFeedbackConstants.VIRTUAL_KEY
        }
        delegate.destination.fragment.view?.performHapticFeedback(feedback)
    }
}
