import { BridgeComponent } from '@hotwired/hotwire-native-bridge';

/*
 * Haptic feedback (bridge--haptic). Put on the success flash:
 * <p data-controller="bridge--haptic" data-bridge-feedback="success">Expense saved</p>
 * Does nothing in a browser.
 */
export default class extends BridgeComponent {
    static component = 'haptic';

    connect() {
        super.connect();
        this.send('vibrate', { feedback: this.bridgeElement.bridgeAttribute('feedback') ?? 'success' });
    }
}
