import HotwireNative
import UIKit

/// The month stepper (bridge--menu) → a calendar button on the left of the title bar,
/// opening a native menu. The selected index goes back to the web, which follows that link.
final class MenuComponent: BridgeComponent {
    override class var name: String { "menu" }

    override func onReceive(message: Message) {
        guard message.event == "connect",
              let data: MessageData = message.data(),
              let viewController = delegate?.destination as? UIViewController
        else { return }

        let actions = data.items.map { item in
            UIAction(title: item.title) { [unowned self] _ in
                reply(to: "connect", with: SelectionMessageData(selectedIndex: item.index))
            }
        }

        let button = UIBarButtonItem(
            title: data.title,
            image: UIImage(systemName: "calendar"),
            menu: UIMenu(title: data.title, children: actions)
        )

        viewController.navigationItem.leftItemsSupplementBackButton = true
        viewController.navigationItem.leftBarButtonItem = button
    }
}

private extension MenuComponent {
    struct MessageData: Decodable {
        let title: String
        let items: [Item]
    }

    struct Item: Decodable {
        let title: String
        let index: Int
    }

    struct SelectionMessageData: Encodable {
        let selectedIndex: Int
    }
}
