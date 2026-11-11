import HotwireNative
import UIKit

@main
final class AppDelegate: UIResponder, UIApplicationDelegate {
    func application(_ application: UIApplication, didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]?) -> Bool {
        // Path rules (Archives as a modal…): bundled copy first, then the live one from Symfony.
        Hotwire.loadPathConfiguration(from: [
            .file(Bundle.main.url(forResource: "path-configuration", withExtension: "json")!),
            .server(Server.pathConfigurationURL),
        ])

        Hotwire.config.applicationUserAgentPrefix = "Remit;"
        Hotwire.config.showDoneButtonOnModals = true
        Hotwire.config.backButtonDisplayMode = .minimal
        #if DEBUG
        Hotwire.config.debugLoggingEnabled = true
        #endif

        return true
    }

    func application(_ application: UIApplication, configurationForConnecting connectingSceneSession: UISceneSession, options: UIScene.ConnectionOptions) -> UISceneConfiguration {
        UISceneConfiguration(name: "Default Configuration", sessionRole: connectingSceneSession.role)
    }
}
