import type Orders from '../interfaces/orders';

export const getAllOrders = async (): Promise<Orders[]> => {
    try {
        const response = await fetch("http://localhost:8080/orders", {
            headers: {
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error("Failed to fetch orders");
        }

        const data = await response.json();

        if (!data || !data.orders) {
            throw new Error("No orders were fetched from the server");
        }

        return data.orders;
    } catch (error) {
        console.error("Failed to fetch all orders from the server: ", error);
        return [];
    }
}


export const getUserOrders = async (user_id: string): Promise<Orders[]> => {
    try {
        console.log("user_id: ", user_id);
        const response = await fetch(`http://localhost:8080/orders/user/${user_id}`, {
            headers: {
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error("Failed to fetch user orders");
        }

        const data = await response.json();

        if (!data || !data.orders.length) {
            throw new Error("No orders were fetched from the server");
        }

        return data.orders;
    } catch (error) {
        console.error("Failed to fetch user orders from the server: ", error);
        return [];
    }
}

export const createOrder = async (requestData: Orders): Promise<Orders | null> => {
    try {
        const response = await fetch("http://localhost:8080/orders/create", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(requestData),
        });

        if (!response.ok) {
            throw new Error("Failed to create order");
        }

        const data = await response.json();
        return data;
    } catch (error) {
        console.error("Failed to create order: ", error);
        return null;
    }
}

export const updateOrder = async (orderId: number, updateData: any): Promise<any> => {
    try {
        const response = await fetch(`http://localhost:8080/orders/${orderId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(updateData),
        });

        if (!response.ok) {
            throw new Error("Failed to update order");
        }

        const data = await response.json();
        return data;
    } catch (error) {
        console.error("Failed to update order: ", error);
        return null;
    }
}


export const deleteOrder = async (orderId: number): Promise<any> => {
    try {
        const response = await fetch(`http://localhost:8080/orders/${orderId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error("Failed to delete order");
        }

        const data = await response.json();
        return data;
    } catch (error) {
        console.error("Failed to delete order: ", error);
        return null;
    }
}


