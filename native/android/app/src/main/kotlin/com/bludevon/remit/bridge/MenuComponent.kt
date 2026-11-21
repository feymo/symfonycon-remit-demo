package com.bludevon.remit.bridge

import android.view.Menu
import android.view.MenuItem
import com.bludevon.remit.R
import dev.hotwire.core.bridge.BridgeComponent
import dev.hotwire.core.bridge.BridgeDelegate
import dev.hotwire.core.bridge.Message
import dev.hotwire.navigation.destinations.HotwireDestination
import kotlinx.serialization.Serializable

/**
 * The month stepper (bridge--menu) → a calendar action opening a native drop-down menu.
 * The selected index goes back to the web, which follows that link.
 */
class MenuComponent(
    name: String,
    private val delegate: BridgeDelegate<HotwireDestination>,
) : BridgeComponent<HotwireDestination>(name, delegate) {

    override fun onReceive(message: Message) {
        if (message.event != "connect") return
        val data = message.data<MessageData>() ?: return
        val menu = delegate.destination.toolbarForNavigation()?.menu ?: return

        menu.removeItem(ITEM_ID)
        val subMenu = menu.addSubMenu(Menu.NONE, ITEM_ID, 50, data.title)
        subMenu.item.setIcon(R.drawable.ic_calendar)
        subMenu.item.setShowAsAction(MenuItem.SHOW_AS_ACTION_ALWAYS)

        data.items.forEach { item ->
            subMenu.add(item.title).setOnMenuItemClickListener {
                replyTo("connect", SelectionMessageData(item.index))
                true
            }
        }
    }

    @Serializable
    data class MessageData(val title: String, val items: List<Item>)

    @Serializable
    data class Item(val title: String, val index: Int)

    @Serializable
    data class SelectionMessageData(val selectedIndex: Int)

    private companion object {
        const val ITEM_ID = 1002
    }
}
