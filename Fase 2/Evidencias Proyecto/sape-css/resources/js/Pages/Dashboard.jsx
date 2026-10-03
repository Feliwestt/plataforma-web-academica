import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Dashboard({ auth }) {
    const fechaActual = new Date().toLocaleDateString('es-CL', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
    });

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <h2 className="text-xl font-semibold leading-tight text-[#002855]">
                    Portal Docente - Colegio San Sebastián
                </h2>
            }
        >
            <Head title="Inicio" />

            <div className="py-12 bg-gray-50 min-h-screen">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">
                    
                    {/* Cabecera de Bienvenida */}
                    <div className="bg-white rounded-xl p-8 shadow-sm border border-gray-200 flex flex-col md:flex-row items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-extrabold text-[#002855]">
                                Bienvenido, {auth.user.name}
                            </h1>
                            <p className="mt-2 text-gray-600 capitalize">
                                {fechaActual}
                            </p>
                        </div>
                        <div className="mt-4 md:mt-0 bg-blue-50 text-[#002855] px-4 py-2 rounded-lg font-semibold border border-blue-100">
                            Año Escolar 2026
                        </div>
                    </div>

                    {/* Tarjetas de Acceso Directo */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        {/* Card: Profesor Jefe */}
                        <Link href={route('profesor.jefe')} className="group block">
                            <div className="bg-white rounded-xl overflow-hidden shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 transform group-hover:-translate-y-1 h-full">
                                <div className="h-2 bg-[#002855]"></div>
                                <div className="p-6">
                                    <div className="flex items-center justify-between">
                                        <h2 className="text-xl font-bold text-gray-800 group-hover:text-[#002855] transition-colors">
                                            Mi Jefatura
                                        </h2>
                                        <div className="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center text-[#002855]">
                                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                        </div>
                                    </div>
                                    <p className="mt-4 text-gray-500 text-sm">
                                        Revisa el historial completo de calificaciones de tu curso y el rendimiento general por subsector.
                                    </p>
                                    <div className="mt-6 flex items-center text-[#B91C1C] font-semibold text-sm group-hover:text-red-800">
                                        Acceder al panel <span className="ml-2">→</span>
                                    </div>
                                </div>
                            </div>
                        </Link>

                        {/* Card: Profesor de Asignatura */}
                        <Link href={route('profesor.asignatura')} className="group block">
                            <div className="bg-white rounded-xl overflow-hidden shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 transform group-hover:-translate-y-1 h-full">
                                <div className="h-2 bg-[#B91C1C]"></div>
                                <div className="p-6">
                                    <div className="flex items-center justify-between">
                                        <h2 className="text-xl font-bold text-gray-800 group-hover:text-[#B91C1C] transition-colors">
                                            Mis Asignaturas
                                        </h2>
                                        <div className="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center text-[#B91C1C]">
                                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                        </div>
                                    </div>
                                    <p className="mt-4 text-gray-500 text-sm">
                                        Visualiza las calificaciones y gestiona el rendimiento de los alumnos en tus clases específicas.
                                    </p>
                                    <div className="mt-6 flex items-center text-[#002855] font-semibold text-sm group-hover:text-blue-800">
                                        Acceder al panel <span className="ml-2">→</span>
                                    </div>
                                </div>
                            </div>
                        </Link>

                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}