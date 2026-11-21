import HotwireNative
import UIKit

/// <p data-controller="bridge--haptic" data-bridge-feedback="success"> on the "Expense saved." flash
/// → the phone taps your hand when the expense is saved.
final class HapticComponent: BridgeComponent {
    override class var name: String { "haptic" }

    override func onReceive(message: Message) {
        guard message.event == "vibrate" else { return }

        let data: MessageData? = message.data()
        let generator = UINotificationFeedbackGenerator()

        switch data?.feedback {
        case "warning": generator.notificationOccurred(.warning)
        case "error": generator.notificationOccurred(.error)
        default: generator.notificationOccurred(.success)
        }
    }
}

private extension HapticComponent {
    struct MessageData: Decodable {
        let feedback: String?
    }
}
