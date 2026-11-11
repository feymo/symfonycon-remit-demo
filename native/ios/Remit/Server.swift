import Foundation

/// Where the Symfony app runs: the FrankenPHP container on the Mac (plain HTTP, see compose.yaml).
enum Server {
    static var baseURL: URL {
        #if targetEnvironment(simulator)
        let host = "localhost"
        #else
        // The Mac's Bonjour name (macbook-pro.local), written into Info.plist by generate.sh.
        // Lowercased: WebKit lowercases hosts, and Hotwire Native compares them as plain strings —
        // "MacBook-Pro.local" ≠ "macbook-pro.local" would send every page to an in-app Safari sheet.
        let host = (Bundle.main.object(forInfoDictionaryKey: "RemitServerHost") as? String ?? "localhost").lowercased()
        #endif
        return URL(string: "http://\(host)")!
    }

    static var startURL: URL { baseURL.appending(path: "expenses") }
    static var pathConfigurationURL: URL { baseURL.appending(path: "config/ios_v1.json") }
}
