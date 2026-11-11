import HotwireNative
import UIKit

final class SceneDelegate: UIResponder, UIWindowSceneDelegate {
    var window: UIWindow?

    private lazy var navigator = Navigator(configuration: .init(name: "main", startLocation: Server.startURL))

    func scene(_ scene: UIScene, willConnectTo session: UISceneSession, options connectionOptions: UIScene.ConnectionOptions) {
        guard let windowScene = scene as? UIWindowScene else { return }

        window = UIWindow(windowScene: windowScene)
        window?.rootViewController = navigator.rootViewController
        window?.makeKeyAndVisible()

        navigator.start()
    }
}
