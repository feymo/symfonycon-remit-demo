import { BridgeComponent } from '@hotwired/hotwire-native-bridge';

/*
 * Title-bar button (bridge--button).
 * <a href="…" data-controller="bridge--button" data-bridge-title="New expense" data-bridge-ios-image="plus">
 * The link stays in the page for the browser; the app draws the native button,
 * and app.css hides this element once the app supports "button".
 */
export default class extends BridgeComponent {
    static component = 'button';

    connect() {
        super.connect();

        const title = this.bridgeElement.title;
        const image = this.bridgeElement.bridgeAttribute('ios-image');

        this.send('connect', { title, image }, () => {
            this.element.click();
        });
    }
}
