import org.jetbrains.kotlin.gradle.dsl.JvmTarget

plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.android")
    id("org.jetbrains.kotlin.plugin.serialization")
}

val remitBaseUrl = providers.gradleProperty("remit.baseUrl").getOrElse("http://10.0.2.2")

android {
    namespace = "com.bludevon.remit"
    compileSdk = 35

    defaultConfig {
        applicationId = "com.bludevon.remit"
        minSdk = 28
        targetSdk = 35
        versionCode = 1
        versionName = "1.0"

        buildConfigField("String", "BASE_URL", "\"$remitBaseUrl\"")
    }

    buildTypes {
        release {
            isMinifyEnabled = false
        }
    }

    buildFeatures {
        buildConfig = true
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }
}

kotlin {
    compilerOptions {
        jvmTarget.set(JvmTarget.JVM_17)
    }
}

dependencies {
    implementation("dev.hotwire:core:1.3.1")
    implementation("dev.hotwire:navigation-fragments:1.3.1")
    implementation("com.google.android.material:material:1.12.0")
    implementation("org.jetbrains.kotlinx:kotlinx-serialization-json:1.8.1")
}
