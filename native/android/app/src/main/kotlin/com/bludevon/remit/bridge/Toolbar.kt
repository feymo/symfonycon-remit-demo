package com.bludevon.remit.bridge

import android.view.Menu
import android.view.MenuItem
import androidx.appcompat.widget.Toolbar

/** Adds (or replaces) one always-visible item in the screen's toolbar. */
internal fun Toolbar.putAction(itemId: Int, order: Int, title: String, onClick: () -> Unit): MenuItem {
    menu.removeItem(itemId)
    return menu.add(Menu.NONE, itemId, order, title).apply {
        setShowAsAction(MenuItem.SHOW_AS_ACTION_ALWAYS)
        setOnMenuItemClickListener {
            onClick()
            true
        }
    }
}
