package com.bludevon.remit.bridge

import android.view.MenuItem
import dev.hotwire.core.bridge.BridgeComponent
import dev.hotwire.core.bridge.BridgeDelegate
import dev.hotwire.core.bridge.Message
import dev.hotwire.navigation.destinations.HotwireDestination
import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

/**
 * The form's submit button (data-bridge--form-target="submit") → "Save" in the toolbar.
 * Adapted from the Hotwire Native demo app (MIT).
 */
class FormComponent(
    name: String,
    private val delegate: BridgeDelegate<HotwireDestination>,
) : BridgeComponent<HotwireDestination>(name, delegate) {

    private var submitItem: MenuItem? = null

    override fun onReceive(message: Message) {
        when (message.event) {
            "connect" -> {
                val data = message.data<MessageData>() ?: return
                val toolbar = delegate.destination.toolbarForNavigation() ?: return
                submitItem = toolbar.putAction(itemId = ITEM_ID, order = 999, title = data.title) {
                    replyTo("connect")
                }
            }
            "submitEnabled" -> submitItem?.isEnabled = true
            "submitDisabled" -> submitItem?.isEnabled = false
        }
    }

    @Serializable
    data class MessageData(@SerialName("submitTitle") val title: String)

    private companion object {
        const val ITEM_ID = 1003
    }
}
