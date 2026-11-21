import { BridgeComponent, BridgeElement } from '@hotwired/hotwire-native-bridge';

/*
 * Form submit in the title bar (bridge--form), as in the Hotwire Native docs.
 * The in-page submit button carries data-bridge--form-target="submit".
 */
export default class extends BridgeComponent {
    static component = 'form';
    static targets = ['submit'];

    submitTargetConnected(target) {
        const submitTitle = new BridgeElement(target).title;

        this.send('connect', { submitTitle }, () => {
            target.click();
        });
    }
}
