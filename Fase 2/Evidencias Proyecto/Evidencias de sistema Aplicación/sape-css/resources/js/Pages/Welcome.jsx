import { Head, Link } from '@inertiajs/react';

export default function Welcome({ auth }) {
    return (
        <>
            <Head title="Bienvenidos - Colegio San Sebastián" />
            
            <div className="min-h-screen flex flex-col font-sans">
                
                {/* Barra de Navegación Pública */}
                <header className="bg-[#002855] py-4 shadow-md z-10 relative">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 bg-white rounded-full flex items-center justify-center text-[#002855] font-bold text-xl shadow-inner">
                                CSS
                            </div>
                            <span className="font-bold text-white text-xl tracking-wide hidden sm:block">
                                Colegio San Sebastián
                            </span>
                        </div>
                        <nav className="flex gap-4 items-center">
                            {auth.user ? (
                                <Link 
                                    href={route('dashboard')} 
                                    className="text-[#002855] bg-white hover:bg-gray-100 px-5 py-2 rounded-md font-bold transition-colors shadow-sm"
                                >
                                    Ir a mi Portal
                                </Link>
                            ) : (
                                <Link 
                                    href={route('login')} 
                                    className="text-white bg-[#B91C1C] hover:bg-red-800 px-6 py-2 rounded-md font-bold transition-all shadow-md hover:shadow-lg"
                                >
                                    Iniciar Sesión
                                </Link>
                            )}
                        </nav>
                    </div>
                </header>

                {/* Hero Section (Sección Principal con fondo Azul) */}
                <main className="flex-grow flex items-center justify-center bg-gradient-to-b from-[#002855] to-[#001533] px-4 sm:px-6 relative overflow-hidden">
                    
                    {/* Elemento decorativo de fondo */}
                    <div className="absolute top-0 left-0 w-full h-full opacity-10 pointer-events-none">
                        <div className="absolute w-96 h-96 bg-white rounded-full blur-3xl -top-20 -left-20"></div>
                        <div className="absolute w-96 h-96 bg-[#B91C1C] rounded-full blur-3xl bottom-10 right-10"></div>
                    </div>

                    <div className="max-w-4xl text-center space-y-8 relative z-10">
                        <div className="inline-block bg-[#B91C1C] text-white px-4 py-1 rounded-full text-sm font-semibold tracking-wider uppercase mb-4">
                            Plataforma Institucional
                        </div>
                        
                        <h1 className="text-4xl md:text-6xl lg:text-7xl font-extrabold text-white tracking-tight drop-shadow-md">
                            Sistema de Gestión <br /> 
                            <span className="text-transparent bg-clip-text bg-gradient-to-r from-white to-blue-200">
                                Académica
                            </span>
                        </h1>
                        
                        <p className="text-lg md:text-xl text-blue-100 max-w-2xl mx-auto font-light leading-relaxed">
                            Portal oficial del Colegio San Sebastián de Melipilla. Un espacio centralizado para gestionar el rendimiento escolar, calificaciones y recursos de nuestra comunidad educativa.
                        </p>
                        
                        <div className="pt-8 flex flex-col sm:flex-row justify-center gap-4">
                            {auth.user ? (
                                <Link 
                                    href={route('dashboard')} 
                                    className="inline-flex justify-center items-center bg-[#B91C1C] text-white font-bold text-lg px-8 py-4 rounded-lg hover:bg-red-800 transition transform hover:-translate-y-1 shadow-[0_4px_14px_0_rgba(185,28,28,0.39)]"
                                >
                                    Ingresar a mi Portal →
                                </Link>
                            ) : (
                                <Link 
                                    href={route('login')} 
                                    className="inline-flex justify-center items-center bg-[#B91C1C] text-white font-bold text-lg px-8 py-4 rounded-lg hover:bg-red-800 transition transform hover:-translate-y-1 shadow-[0_4px_14px_0_rgba(185,28,28,0.39)]"
                                >
                                    Acceder al Sistema
                                </Link>
                            )}
                        </div>
                    </div>
                </main>

                {/* Footer simple */}
                <footer className="bg-white border-t border-gray-200 py-6 text-center">
                    <p className="text-gray-500 text-sm font-medium">
                        © {new Date().getFullYear()} Colegio San Sebastián de Melipilla. Todos los derechos reservados.
                    </p>
                </footer>

            </div>
        </>
    );
}