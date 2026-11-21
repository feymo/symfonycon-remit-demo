import { BridgeComponent, BridgeElement } from '@hotwired/hotwire-native-bridge';

/*
 * Native menu (bridge--menu): the month stepper becomes a title-bar menu in the app.
 * Each link marked data-bridge--menu-target="item" is one menu entry.
 */
export default class extends BridgeComponent {
    static component = 'menu';
    static targets = ['item'];

    connect() {
        super.connect();

        const title = this.bridgeElement.title;
        const items = this.itemTargets.map((element, index) => ({
            title: new BridgeElement(element).title,
            index,
        }));

        this.send('connect', { title, items }, (message) => {
            this.itemTargets[message.data.selectedIndex]?.click();
        });
    }
}
