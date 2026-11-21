package com.bludevon.remit.bridge

import com.bludevon.remit.R
import dev.hotwire.core.bridge.BridgeComponent
import dev.hotwire.core.bridge.BridgeDelegate
import dev.hotwire.core.bridge.Message
import dev.hotwire.navigation.destinations.HotwireDestination
import kotlinx.serialization.Serializable

/**
 * <a data-controller="bridge--button" data-bridge-title="New expense" data-bridge-ios-image="plus">
 * → an action in the toolbar. Tapping it clicks the link in the page.
 */
class ButtonComponent(
    name: String,
    private val delegate: BridgeDelegate<HotwireDestination>,
) : BridgeComponent<HotwireDestination>(name, delegate) {

    override fun onReceive(message: Message) {
        if (message.event != "connect") return
        val data = message.data<MessageData>() ?: return
        val toolbar = delegate.destination.toolbarForNavigation() ?: return

        toolbar.putAction(itemId = ITEM_ID, order = 100, title = data.title) {
            replyTo("connect")
        }.apply {
            if (data.image == "plus") setIcon(R.drawable.ic_add)
        }
    }

    @Serializable
    data class MessageData(val title: String, val image: String? = null)

    private companion object {
        const val ITEM_ID = 1001
    }
}
