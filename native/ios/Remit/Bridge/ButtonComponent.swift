import HotwireNative
import UIKit

/// <a data-controller="bridge--button" data-bridge-title="New expense" data-bridge-ios-image="plus">
/// → a button on the right of the title bar. Tapping it clicks the link in the page.
final class ButtonComponent: BridgeComponent {
    override class var name: String { "button" }

    override func onReceive(message: Message) {
        guard message.event == "connect",
              let data: MessageData = message.data(),
              let viewController = delegate?.destination as? UIViewController
        else { return }

        let action = UIAction { [unowned self] _ in
            reply(to: "connect")
        }

        let item: UIBarButtonItem
        if let image = data.image.flatMap({ UIImage(systemName: $0) }) {
            item = UIBarButtonItem(image: image, primaryAction: action)
            item.accessibilityLabel = data.title
        } else {
            item = UIBarButtonItem(title: data.title, primaryAction: action)
        }

        viewController.navigationItem.rightBarButtonItem = item
    }
}

private extension ButtonComponent {
    struct MessageData: Decodable {
        let title: String
        let image: String?
    }
}
