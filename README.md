<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Sockeon WebSocket Chat

This Laravel application integrates [Sockeon](https://sockeon.com) - a framework-agnostic PHP WebSocket and HTTP server library - to provide real-time chat functionality.

### Features

- Real-time bidirectional communication via WebSocket
- Chat message broadcasting to all connected clients
- User join/leave notifications
- Typing indicators
- Modern, responsive chat interface
- Automatic reconnection on disconnect

### Getting Started

#### 1. Environment Configuration

The Sockeon server configuration can be customized via environment variables in your `.env` file:

```env
SOCKEON_HOST=0.0.0.0
SOCKEON_PORT=8080
```

#### 2. Start the WebSocket Server

Run the Sockeon WebSocket server using the Artisan command:

```bash
php artisan sockeon:serve
```

The server will start on `ws://localhost:8080` by default. You should see output like:

```
Registered controller: App\WebSocket\ChatController
Starting WebSocket server on ws://0.0.0.0:8080
Press Ctrl+C to stop
```

#### 3. Start the Laravel HTTP Server

In a separate terminal, start the Laravel development server:

```bash
php artisan serve
```

#### 4. Access the Chat Interface

Open your browser and navigate to:

```
http://localhost:8000/chat
```

You can open multiple browser tabs to test the real-time chat functionality between different clients.

### Architecture

The application runs two separate servers:

- **Laravel HTTP Server** (port 8000): Serves the web application and chat interface
- **Sockeon WebSocket Server** (port 8080): Handles real-time WebSocket connections and message broadcasting

The chat client connects to the WebSocket server for real-time communication while being served by the Laravel HTTP server.

### Files Structure

- `app/WebSocket/ChatController.php` - WebSocket event handlers
- `app/Console/Commands/SockeonServeCommand.php` - Artisan command to start the server
- `config/sockeon.php` - Sockeon server configuration
- `resources/views/chat.blade.php` - Chat client interface
- `routes/web.php` - Web routes including `/chat`

### Documentation

For more information about Sockeon, visit:
- [Sockeon Documentation](https://sockeon.com)
- [Sockeon GitHub](https://github.com/sockeon/sockeon)

## License


We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
