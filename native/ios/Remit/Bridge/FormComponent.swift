import HotwireNative
import UIKit

/// The form's submit button (data-bridge--form-target="submit") → "Save" in the title bar.
/// Adapted from the Hotwire Native demo app (MIT).
final class FormComponent: BridgeComponent {
    override class var name: String { "form" }

    private weak var submitItem: UIBarButtonItem?

    override func onReceive(message: Message) {
        switch message.event {
        case "connect":
            guard let data: MessageData = message.data(),
                  let viewController = delegate?.destination as? UIViewController
            else { return }

            let action = UIAction { [unowned self] _ in
                reply(to: "connect")
            }
            let item = UIBarButtonItem(title: data.submitTitle, primaryAction: action)
            item.style = .done
            viewController.navigationItem.rightBarButtonItem = item
            submitItem = item
        case "submitEnabled":
            submitItem?.isEnabled = true
        case "submitDisabled":
            submitItem?.isEnabled = false
        default:
            break
        }
    }
}

private extension FormComponent {
    struct MessageData: Decodable {
        let submitTitle: String
    }
}
