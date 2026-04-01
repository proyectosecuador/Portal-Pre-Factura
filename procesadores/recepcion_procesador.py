#!/usr/bin/env python
# -*- coding: utf-8 -*-

"""
Procesador optimizado para Recepción
"""

import pandas as pd
import logging
import os
import sys
from datetime import datetime

logger = logging.getLogger(__name__)

class RecepcionProcesador:
    """Procesador para archivos de recepción"""
    
    # Mapeo de columnas esperadas
    COLUMNAS = {
        'RECEIPTKEY': {'col': 'C', 'tipo': 'string'},
        'SKU': {'col': 'D', 'tipo': 'string'},
        'STORERKEY': {'col': 'E', 'tipo': 'string'},
        'QTYRECEIVED': {'col': 'H', 'tipo': 'float'},
        'UOM': {'col': 'I', 'tipo': 'string'},
        'STATUS': {'col': 'O', 'tipo': 'string'},
        'DATERECEIVED': {'col': 'AH', 'tipo': 'date'},
        'EXTERNRECEIPTKEY': {'col': 'AN', 'tipo': 'string'},
        'TYPE': {'col': 'BP', 'tipo': 'string'}
    }
    
    HEADERS = ['RECEIPTKEY', 'SKU', 'STORERKEY', 'UNIDADES', 'CAJAS', 'PALLETS', 
               'STATUS', 'DATERECEIVED', 'EXTERNRECEIPTKEY', 'TYPE']
    
    STATUS_VALIDOS = ['11', '15']
    
    def __init__(self):
        self.logger = logger
    
    def _excel_col_to_index(self, col_letter):
        """Convierte letra de columna a índice (ej: 'C' -> 2)"""
        result = 0
        for char in col_letter:
            result = result * 26 + (ord(char.upper()) - ord('A') + 1)
        return result - 1
    
    def _extraer_parte_entera(self, valor):
        """Extrae la parte entera de un número, manejando strings"""
        try:
            if pd.isna(valor):
                return 0
            if isinstance(valor, str):
                import re
                match = re.search(r'(\d+)', valor)
                return int(match.group(1)) if match else 0
            return int(float(valor))
        except:
            return 0
    
    def _convertir_fecha(self, valor):
        """Convierte fecha a formato YYYY-MM-DD manejando diferentes formatos"""
        if pd.isna(valor) or valor == '' or valor is None:
            return None
        
        try:
            # Si ya es datetime, convertir a string YYYY-MM-DD
            if isinstance(valor, (datetime, pd.Timestamp)):
                return valor.strftime('%Y-%m-%d')
            
            # Si es string
            if isinstance(valor, str):
                valor_str = str(valor).strip()
                
                # Intentar diferentes formatos
                formatos = [
                    '%d/%m/%y %H:%M',      # 30/3/26 09:25
                    '%d/%m/%Y %H:%M',      # 30/03/2026 09:25
                    '%d/%m/%y',            # 30/3/26
                    '%d/%m/%Y',            # 30/03/2026
                    '%Y-%m-%d',            # 2026-03-30
                    '%Y/%m/%d',            # 2026/03/30
                    '%d-%m-%Y',            # 30-03-2026
                    '%m/%d/%Y',            # 03/30/2026 (formato US)
                ]
                
                for fmt in formatos:
                    try:
                        fecha = datetime.strptime(valor_str, fmt)
                        return fecha.strftime('%Y-%m-%d')
                    except ValueError:
                        continue
                
                # Si no funciona con los formatos, intentar con pandas
                try:
                    fecha = pd.to_datetime(valor_str, dayfirst=True)
                    return fecha.strftime('%Y-%m-%d')
                except:
                    pass
                
                self.logger.warning(f"No se pudo convertir fecha: {valor_str}")
                return valor_str
            
            # Si es número (fecha de Excel)
            if isinstance(valor, (int, float)):
                try:
                    fecha = pd.Timestamp.fromordinal(int(valor) - 693594)
                    return fecha.strftime('%Y-%m-%d')
                except:
                    pass
            
            return str(valor)
            
        except Exception as e:
            self.logger.error(f"Error convirtiendo fecha {valor}: {e}")
            return None
    
    def _aplicar_filtros_fecha(self, df, col_fecha, fecha_desde, fecha_hasta):
        """Aplica filtros de fecha al dataframe"""
        stats = {
            'filas_filtradas_fecha': 0,
            'fecha_min': None,
            'fecha_max': None
        }
        
        if col_fecha not in df.columns or len(df) == 0:
            return df, stats
        
        # Guardar cantidad original antes de filtrar
        original_len = len(df)
        
        # Convertir columna a datetime con dayfirst=True (día/mes/año)
        try:
            df[col_fecha] = pd.to_datetime(df[col_fecha], errors='coerce', dayfirst=True)
        except:
            # Si falla, intentar con el método manual
            df[col_fecha] = df[col_fecha].apply(self._convertir_fecha)
            df[col_fecha] = pd.to_datetime(df[col_fecha], errors='coerce', dayfirst=True)
        
        # Guardar estadísticas de fechas originales (antes de filtrar)
        fechas_validas = df[df[col_fecha].notna()]
        if len(fechas_validas) > 0:
            stats['fecha_min'] = fechas_validas[col_fecha].min().strftime('%Y-%m-%d')
            stats['fecha_max'] = fechas_validas[col_fecha].max().strftime('%Y-%m-%d')
        
        # Aplicar filtros de fecha - IMPORTANTE: convertir las fechas de entrada
        df_filtrado = df.copy()
        
        if fecha_desde:
            try:
                # Convertir la fecha desde a datetime (formato YYYY-MM-DD)
                fecha_desde_dt = pd.to_datetime(fecha_desde, format='%Y-%m-%d')
                print(f"[FILTRO] Filtrando desde: {fecha_desde_dt}")
                df_filtrado = df_filtrado[df_filtrado[col_fecha] >= fecha_desde_dt]
            except Exception as e:
                print(f"[FILTRO] Error con fecha_desde {fecha_desde}: {e}")
                # Intentar con dayfirst
                try:
                    fecha_desde_dt = pd.to_datetime(fecha_desde, dayfirst=True)
                    df_filtrado = df_filtrado[df_filtrado[col_fecha] >= fecha_desde_dt]
                except:
                    pass
        
        if fecha_hasta:
            try:
                # Convertir la fecha hasta a datetime (incluir hasta el final del día)
                fecha_hasta_dt = pd.to_datetime(fecha_hasta, format='%Y-%m-%d') + pd.Timedelta(days=1)
                print(f"[FILTRO] Filtrando hasta: {fecha_hasta_dt}")
                df_filtrado = df_filtrado[df_filtrado[col_fecha] <= fecha_hasta_dt]
            except Exception as e:
                print(f"[FILTRO] Error con fecha_hasta {fecha_hasta}: {e}")
                # Intentar con dayfirst
                try:
                    fecha_hasta_dt = pd.to_datetime(fecha_hasta, dayfirst=True) + pd.Timedelta(days=1)
                    df_filtrado = df_filtrado[df_filtrado[col_fecha] <= fecha_hasta_dt]
                except:
                    pass
        
        # Calcular cuántas filas fueron filtradas
        stats['filas_filtradas_fecha'] = original_len - len(df_filtrado)
        print(f"[FILTRO] Filas antes: {original_len}, después: {len(df_filtrado)}, filtradas: {stats['filas_filtradas_fecha']}")
        
        # Convertir fechas a string para JSON
        df_filtrado[col_fecha] = df_filtrado[col_fecha].apply(
            lambda x: x.strftime('%Y-%m-%d') if pd.notna(x) else ''
        )
        
        return df_filtrado, stats
    
    def _convertir_dataframe_a_lista(self, df):
        """Convierte DataFrame a lista de listas para JSON"""
        if df.empty:
            return []
        
        # Asegurar que las fechas estén en formato string
        if 'DATERECEIVED' in df.columns:
            df['DATERECEIVED'] = df['DATERECEIVED'].apply(
                lambda x: x if isinstance(x, str) else (x.strftime('%Y-%m-%d') if hasattr(x, 'strftime') else str(x))
            )
        
        return df.fillna('').values.tolist()
    
    def procesar(self, archivo_path, fecha_desde=None, fecha_hasta=None, request_id=None):
        """Procesa el archivo de recepción"""
        self.logger.info(f"[{request_id}] Procesando archivo de recepción: {archivo_path}")
        self.logger.info(f"[{request_id}] Filtros fecha - Desde: {fecha_desde}, Hasta: {fecha_hasta}")
        print(f"[{request_id}] Filtros fecha - Desde: {fecha_desde}, Hasta: {fecha_hasta}")
        
        try:
            # Leer Excel
            excel_file = pd.ExcelFile(archivo_path, engine='openpyxl')
            hoja = self._encontrar_hoja_detail(excel_file)
            
            df = pd.read_excel(archivo_path, sheet_name=hoja, engine='openpyxl', header=None)
            self.logger.info(f"[{request_id}] Hoja: {hoja}, Filas totales: {len(df)}")
            print(f"[{request_id}] Hoja: {hoja}, Filas totales: {len(df)}")
            
            # Extraer columnas específicas
            df_resultado = pd.DataFrame()
            for nombre, info in self.COLUMNAS.items():
                idx = self._excel_col_to_index(info['col'])
                if idx < len(df.columns) and len(df) > 1:
                    df_resultado[nombre] = df.iloc[1:, idx].reset_index(drop=True)
            
            # Limpiar filas vacías
            df_resultado = df_resultado.dropna(subset=['RECEIPTKEY', 'SKU'], how='all')
            self.logger.info(f"[{request_id}] Después de limpiar vacíos: {len(df_resultado)} filas")
            print(f"[{request_id}] Después de limpiar vacíos: {len(df_resultado)} filas")
            
            # Filtrar por STATUS
            if 'STATUS' in df_resultado.columns and len(df_resultado) > 0:
                df_resultado['STATUS_STR'] = df_resultado['STATUS'].astype(str).str.strip()
                df_resultado = df_resultado[df_resultado['STATUS_STR'].isin(self.STATUS_VALIDOS)].copy()
                self.logger.info(f"[{request_id}] Después de filtrar STATUS: {len(df_resultado)} filas")
                print(f"[{request_id}] Después de filtrar STATUS: {len(df_resultado)} filas")
            
            # Procesar cantidades
            if 'QTYRECEIVED' in df_resultado.columns and len(df_resultado) > 0:
                df_resultado['QTY_ENTERO'] = df_resultado['QTYRECEIVED'].apply(self._extraer_parte_entera)
            
            # Aplicar filtros de fecha - PASAR LAS FECHAS CORRECTAMENTE
            df_resultado, stats_fecha = self._aplicar_filtros_fecha(
                df_resultado, 'DATERECEIVED', fecha_desde, fecha_hasta
            )
            self.logger.info(f"[{request_id}] Después de filtrar fechas: {len(df_resultado)} filas")
            print(f"[{request_id}] Después de filtrar fechas: {len(df_resultado)} filas")
            print(f"[{request_id}] Stats fecha: {stats_fecha}")
            
            # Agrupar por RECEIPTKEY + SKU
            if len(df_resultado) > 0:
                df_resultado['GRUPO'] = df_resultado['RECEIPTKEY'].astype(str) + '_' + df_resultado['SKU'].astype(str)
                
                def agregar_grupo(g):
                    if len(g) == 0:
                        return None
                    primero = g.iloc[0]
                    uom = str(primero['UOM']).strip().upper() if pd.notna(primero['UOM']) else ''
                    cantidad = int(g['QTY_ENTERO'].sum()) if 'QTY_ENTERO' in g.columns else 0
                    
                    return pd.Series({
                        'RECEIPTKEY': primero['RECEIPTKEY'],
                        'SKU': primero['SKU'],
                        'STORERKEY': primero['STORERKEY'] if 'STORERKEY' in g.columns else '',
                        'UNIDADES': cantidad if uom == 'UN' else 0,
                        'CAJAS': cantidad if uom == 'CJ' else 0,
                        'PALLETS': cantidad if uom == 'PL' else 0,
                        'STATUS': primero['STATUS'] if 'STATUS' in g.columns else '',
                        'DATERECEIVED': g['DATERECEIVED'].iloc[0] if 'DATERECEIVED' in g.columns else '',
                        'EXTERNRECEIPTKEY': primero['EXTERNRECEIPTKEY'] if 'EXTERNRECEIPTKEY' in g.columns else '',
                        'TYPE': primero['TYPE'] if 'TYPE' in g.columns else ''
                    })
                
                df_agrupado = df_resultado.groupby('GRUPO', group_keys=False).apply(agregar_grupo).reset_index(drop=True)
                self.logger.info(f"[{request_id}] Después de agrupar: {len(df_agrupado)} filas")
                print(f"[{request_id}] Después de agrupar: {len(df_agrupado)} filas")
            else:
                df_agrupado = pd.DataFrame(columns=self.HEADERS)
            
            # Asegurar que las fechas estén en formato correcto
            if 'DATERECEIVED' in df_agrupado.columns and len(df_agrupado) > 0:
                df_agrupado['DATERECEIVED'] = df_agrupado['DATERECEIVED'].apply(
                    lambda x: x if isinstance(x, str) else (x.strftime('%Y-%m-%d') if hasattr(x, 'strftime') else str(x))
                )
            
            # Calcular estadísticas adicionales
            stats = {
                'total_filas': len(df_agrupado),
                'filas_filtradas_fecha': stats_fecha['filas_filtradas_fecha'],
                'fecha_min': stats_fecha['fecha_min'],
                'fecha_max': stats_fecha['fecha_max'],
                'hoja_procesada': hoja,
                'total_unidades': int(df_agrupado['UNIDADES'].sum()) if len(df_agrupado) > 0 else 0,
                'total_cajas': int(df_agrupado['CAJAS'].sum()) if len(df_agrupado) > 0 else 0,
                'total_pallets': int(df_agrupado['PALLETS'].sum()) if len(df_agrupado) > 0 else 0,
                'receiptkeys_unicos': df_agrupado['RECEIPTKEY'].nunique() if len(df_agrupado) > 0 else 0
            }
            
            # Preparar datos para la vista previa
            preview_data = self._convertir_dataframe_a_lista(df_agrupado.head(100))
            full_data = self._convertir_dataframe_a_lista(df_agrupado)
            
            return {
                'success': True,
                'total_registros': len(df_agrupado),
                'headers': self.HEADERS,
                'data': preview_data,
                'data_completa': full_data,
                'stats': stats,
                'metadata': {
                    'archivo_nombre': os.path.basename(archivo_path),
                    'hoja_procesada': hoja,
                    'fechas_aplicadas': {
                        'desde': fecha_desde,
                        'hasta': fecha_hasta
                    }
                }
            }
            
        except Exception as e:
            self.logger.error(f"[{request_id}] Error procesando: {str(e)}", exc_info=True)
            raise
    
    def _encontrar_hoja_detail(self, excel_file):
        """Busca la hoja que contiene 'Detail' en el nombre"""
        sheet_names = excel_file.sheet_names
        
        for name in sheet_names:
            if 'Detail' in name or 'detail' in name:
                return name
        
        return sheet_names[0] if sheet_names else 'Sheet1'